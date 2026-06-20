<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    /**
     * GET /api/me — the authenticated user + plan/limits.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->toApiArray()]);
    }

    /**
     * GET /api/feed — the user's full newspaper: top-level topics (with
     * nested subtopics), each topic's 12-article feed, saved markers, and
     * the keyword watchlist.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $savedFingerprints = $user->savedArticles()->pluck('fingerprint')->all();

        $topicModels = $user->topLevelTopics()
            ->with([
                'articles' => fn ($q) => $q->orderBy('position'),
                'children' => fn ($q) => $q->orderBy('position'),
                'children.articles' => fn ($q) => $q->orderBy('position'),
            ])
            ->orderBy('position')
            ->get();

        return response()->json([
            'topics'             => $topicModels->map(fn ($t) => $this->topic($t, true))->all(),
            'saved_fingerprints' => $savedFingerprints,
            'watchlist'          => $this->watchlist($user, $topicModels),
            'watch_keywords'     => $user->isPro() ? ($user->watch_keywords ?? []) : [],
        ]);
    }

    private function topic(Topic $topic, bool $withChildren = false): array
    {
        $data = [
            'id'                => $topic->id,
            'name'              => $topic->name,
            'parent_id'         => $topic->parent_id,
            'last_refreshed_at' => $topic->last_refreshed_at?->toIso8601String(),
            'articles'          => $topic->articles->map(fn ($a) => $this->article($a))->all(),
        ];

        if ($withChildren) {
            $data['children'] = $topic->children->map(fn ($c) => $this->topic($c))->all();
        }

        return $data;
    }

    private function article(Article $a): array
    {
        return [
            'id'           => $a->id,
            'headline'     => $a->headline,
            'description'  => $a->description,
            'url'          => $a->url,
            'source'       => $a->source,
            'image_url'    => $a->image_url,
            'fingerprint'  => $a->fingerprint,
            'published_at' => $a->published_at?->toIso8601String(),
            'is_read'      => ! is_null($a->read_at),
            'tldr'         => $a->tldr,
        ];
    }

    private function watchlist(User $user, $topicModels): array
    {
        if (! $user->isPro() || empty($user->watch_keywords)) {
            return [];
        }

        $hits = [];
        $scan = function (Topic $topic) use ($user, &$hits) {
            foreach ($topic->articles as $a) {
                $matches = $user->watchMatches($a->headline, $a->description);
                if (! empty($matches)) {
                    $hits[] = $this->article($a) + ['topic_name' => $topic->name, 'matches' => $matches];
                }
            }
        };

        foreach ($topicModels as $topic) {
            $scan($topic);
            foreach ($topic->children as $child) {
                $scan($child);
            }
        }

        return collect($hits)->unique('fingerprint')->values()->take(24)->all();
    }
}
