<?php

namespace App\Services\Articles;

use App\Contracts\ArticleProvider;
use App\Support\FetchedArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Generates realistic placeholder articles with no network calls, so the
 * entire app is clickable before any news APIs are configured. Output is
 * stable within a given day (seeded by topic + date) and rotates daily, so
 * it faithfully simulates the real "fresh stories each morning" behaviour.
 */
class StubArticleProvider implements ArticleProvider
{
    private const SOURCES = [
        ['The Daily Dispatch', 'dailydispatch.example'],
        ['Global Wire', 'globalwire.example'],
        ['The Morning Ledger', 'morningledger.example'],
        ['Frontline Report', 'frontlinereport.example'],
        ['The Beacon', 'thebeacon.example'],
        ['Signal News', 'signalnews.example'],
        ['The Chronicle', 'chronicle.example'],
        ['Newsstand Today', 'newsstandtoday.example'],
        ['The Observer Post', 'observerpost.example'],
        ['Briefing Room', 'briefingroom.example'],
        ['The Current', 'thecurrent.example'],
        ['Headline Hub', 'headlinehub.example'],
    ];

    private const TEMPLATES = [
        'What you need to know about %s today',
        '%s: the story everyone is reading this morning',
        'Inside the latest developments on %s',
        '5 takeaways from this week in %s',
        'Experts weigh in on the future of %s',
        '%s makes headlines after a major update',
        'The numbers behind %s that surprised analysts',
        'A closer look at %s and why it matters now',
        'Breaking: new details emerge on %s',
        '%s explained: everything that happened',
        'How %s is shaping the conversation today',
        'The big question facing %s right now',
    ];

    public function fetch(string $topic, int $count, array $excludeFingerprints = []): array
    {
        $excluded = array_flip($excludeFingerprints);

        // Daily seed: same topic + same day => same stories; rolls over at
        // midnight so each morning's refresh surfaces a fresh set.
        $daySeed = (int) Carbon::now()->format('Ymd');
        $base = crc32(Str::lower($topic)) + $daySeed;

        $articles = [];
        $i = 0;

        // Keep generating (widening) until we have $count non-excluded items.
        // Guard with a generous ceiling so a pathological exclude list can't
        // loop forever.
        while (count($articles) < $count && $i < $count * 6) {
            [$sourceName, $sourceDomain] = self::SOURCES[($base + $i) % count(self::SOURCES)];
            $template = self::TEMPLATES[($base + $i) % count(self::TEMPLATES)];

            $headline = sprintf($template, $this->titleCase($topic));
            $slug = Str::slug($topic.'-'.$i.'-'.$daySeed);
            $url = "https://www.{$sourceDomain}/{$slug}";

            $article = new FetchedArticle(
                headline: $headline,
                description: $this->description($topic, $sourceName),
                url: $url,
                source: $sourceName,
                imageUrl: null,
                publishedAt: Carbon::now()->subHours(($base + $i) % 24),
                // Descending popularity so the first items rank highest.
                popularityScore: round(100 - ($i * 1.7) - (($base + $i) % 7), 2),
            );

            $i++;

            if (isset($excluded[$article->fingerprint()])) {
                continue;
            }

            $articles[] = $article;
        }

        return $articles;
    }

    private function description(string $topic, string $source): string
    {
        $topic = $this->titleCase($topic);

        return "{$source} rounds up the most-read coverage on {$topic} from the "
            ."past day, with context on what changed, who is involved, and what "
            ."to watch next. Tap Read More for the full report.";
    }

    private function titleCase(string $topic): string
    {
        return Str::of($topic)->trim()->title()->value();
    }
}
