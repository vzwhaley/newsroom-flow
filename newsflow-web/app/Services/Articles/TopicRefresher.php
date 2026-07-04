<?php

namespace App\Services\Articles;

use App\Contracts\ArticleProvider;
use App\Contracts\LocationAwareProvider;
use App\Models\Article;
use App\Models\ArticleArchive;
use App\Models\Topic;
use App\Services\Push\WatchlistPusher;
use App\Support\FetchedArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Applies a topic's daily refresh rule:
 *
 *   • A topic always holds up to N (config: articles_per_topic, default 12)
 *     articles so the user always has a full feed to read.
 *   • Each refresh we scour for fresh, popular stories the topic doesn't
 *     already have. Any genuinely new articles are prepended to the top and
 *     an equal number of the oldest articles are dropped — so the feed stays
 *     at N and rotates toward newer stories over time.
 *   • If a topic is brand new (or under-filled), we simply fill up to N.
 *   • If nothing new is found (e.g. a niche topic with no fresh coverage),
 *     the existing feed is preserved untouched — the user still has N to read.
 */
class TopicRefresher
{
    public function __construct(
        private readonly ArticleProvider $provider,
        private readonly ?WatchlistPusher $watchlistPusher = null,
        private readonly ?LocalSources $localSources = null,
    ) {
    }

    /**
     * @return array{topic:string, kept:int, added:int, dropped:int, total:int}
     */
    public function refresh(Topic $topic): array
    {
        $target = (int) config('billing.articles_per_topic', 12);

        $existing = $topic->articles()->get();
        $existingFingerprints = $existing->pluck('fingerprint')->all();

        // Ask the provider for a full set, telling it what we already have so
        // it can prioritise genuinely new stories (and keep scouring to fill).
        // Local-area feeds use the location-aware path (country + curated local
        // outlets) when the provider supports it.
        if ($topic->isArea() && $this->provider instanceof LocationAwareProvider) {
            $sources = $this->localSources ?? new LocalSources();
            $candidates = $this->provider->fetchLocal(
                $topic->searchQuery(),
                $target,
                $existingFingerprints,
                $sources->countryCode($topic),
                $sources->forArea($topic),
            );
        } else {
            $candidates = $this->provider->fetch(
                $topic->searchQuery(),
                $target,
                $existingFingerprints,
            );
        }

        // Drop muted topics and articles from blocked publishers (Pro) before
        // considering anything.
        $user = $topic->user;
        $candidates = array_values(array_filter($candidates, fn ($c) =>
            ! $topic->isMuted($c->headline, $c->description)
            && ! ($user && $user->isSourceBlocked($c->source, $c->url))
        ));

        // Keep only candidates we don't already store, de-duped among
        // themselves, preserving the provider's best-first ordering.
        $newOnes = $this->selectNew($candidates, $existingFingerprints);

        $now = Carbon::now();
        $added = 0;
        $dropped = 0;
        $inserted = [];

        DB::transaction(function () use ($topic, $existing, $newOnes, $target, $now, &$added, &$dropped, &$inserted) {
            $currentCount = $existing->count();

            if ($currentCount < $target) {
                // Under-filled (new or sparse topic): just top up toward target.
                $room = $target - $currentCount;
                $toAdd = array_slice($newOnes, 0, $room);
                $this->insertAtTop($topic, $toAdd, $now);
                $added = count($toAdd);
                $inserted = $toAdd;
            } else {
                // Full feed: prepend new stories, drop an equal number of the
                // oldest so the total stays at target.
                $toAdd = array_slice($newOnes, 0, $target); // never add more than a full refresh
                $added = count($toAdd);
                $inserted = $toAdd;

                if ($added > 0) {
                    // Drop the oldest $added articles (highest position values).
                    $toDrop = $topic->articles()
                        ->orderByDesc('position')
                        ->limit($added)
                        ->get();

                    // Snapshot them to the user's archive (Pro history) before
                    // they're removed from the live feed.
                    $this->archive($topic, $toDrop, $now);

                    Article::whereIn('id', $toDrop->pluck('id'))->delete();
                    $dropped = $toDrop->count();

                    $this->insertAtTop($topic, $toAdd, $now);
                }
            }

            // Renumber positions 0..n-1 top-to-bottom for a stable feed.
            $this->renumber($topic);

            $topic->forceFill([
                'last_refreshed_at'    => $now,
                'last_new_articles_at' => $added > 0 ? $now : $topic->last_new_articles_at,
            ])->save();
        });

        // Priority watchlist push (Pro): fires only for the articles this
        // refresh actually inserted, so a story is never pushed twice.
        if (! empty($inserted) && $this->watchlistPusher) {
            $this->watchlistPusher->pushMatches($topic, $inserted);
        }

        return [
            'topic'   => $topic->name,
            'kept'    => $topic->articles()->count() - $added,
            'added'   => $added,
            'dropped' => $dropped,
            'total'   => $topic->articles()->count(),
        ];
    }

    /**
     * @param  array<int, FetchedArticle>  $candidates
     * @param  array<string>  $existingFingerprints
     * @return array<int, FetchedArticle>
     */
    private function selectNew(array $candidates, array $existingFingerprints): array
    {
        $have = array_flip($existingFingerprints);
        $seen = [];
        $new = [];

        foreach ($candidates as $c) {
            $fp = $c->fingerprint();

            if (isset($have[$fp]) || isset($seen[$fp])) {
                continue;
            }

            $seen[$fp] = true;
            $new[] = $c;
        }

        return $new;
    }

    /**
     * Snapshot rotated-out articles into the owner's archive (Pro history).
     * Best-effort and idempotent (unique on user + fingerprint).
     *
     * @param  \Illuminate\Support\Collection<int, Article>  $articles
     */
    private function archive(Topic $topic, $articles, Carbon $now): void
    {
        $user = $topic->user;

        if (! $user || ! $user->isPro() || $articles->isEmpty()) {
            return;
        }

        foreach ($articles as $a) {
            ArticleArchive::firstOrCreate(
                ['user_id' => $user->id, 'fingerprint' => $a->fingerprint],
                [
                    'topic_name'   => $topic->name,
                    'headline'     => $a->headline,
                    'description'  => $a->description,
                    'url'          => $a->url,
                    'source'       => $a->source,
                    'image_url'    => $a->image_url,
                    'published_at' => $a->published_at,
                    'archived_at'  => $now,
                ],
            );
        }
    }

    /**
     * Insert new articles above the existing feed. We temporarily push them
     * to negative positions (preserving their order) and let renumber() fix
     * everything to a clean 0..n-1 sequence afterwards.
     *
     * @param  array<int, FetchedArticle>  $articles
     */
    private function insertAtTop(Topic $topic, array $articles, Carbon $now): void
    {
        $offset = -count($articles);

        foreach ($articles as $i => $fetched) {
            $data = $fetched->toArray();
            $data['topic_id'] = $topic->id;
            $data['position'] = $offset + $i;
            $data['fetched_at'] = $now;

            $topic->articles()->create($data);
        }
    }

    private function renumber(Topic $topic): void
    {
        $ordered = $topic->articles()
            ->orderBy('position')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        foreach ($ordered as $i => $article) {
            if ($article->position !== $i) {
                $article->forceFill(['position' => $i])->save();
            }
        }
    }
}
