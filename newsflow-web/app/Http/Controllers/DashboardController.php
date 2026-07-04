<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Services\Articles\LocationQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The user's personal newspaper: top-level topics, each with its current
     * 12-article feed and any nested subtopics (also with feeds).
     */
    public function index(Request $request): Response
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

        $topics = $topicModels->map(fn ($topic) => $this->transform($topic, true))->all();

        return Inertia::render('Dashboard', [
            'topics'            => $topics,
            'savedFingerprints' => $savedFingerprints,
            'watchlist'         => $this->watchlist($user, $topicModels),
            'watchKeywords'     => $user->isPro() ? ($user->watch_keywords ?? []) : [],
            'reading'           => \App\Models\ReadingDay::statsFor($user),
            'areas'             => $this->areas($user),
            'geoOptions'        => [
                'states'    => LocationQuery::US_STATES,
                'countries' => LocationQuery::COUNTRIES,
            ],
        ]);
    }

    /**
     * The user's local-area feeds, each with its snapshot + lock state.
     */
    private function areas($user): array
    {
        return $user->areas()->with(['articles' => fn ($q) => $q->orderBy('position')])->get()
            ->map(fn (Topic $area) => [
                'id'           => $area->id,
                'name'         => $area->name,
                'locality'     => $area->locality,
                'region'       => $area->region,
                'postal_code'  => $area->postal_code,
                'country_code' => $area->country_code,
                'locked'       => ! $user->canModifyArea($area),
                'last_refreshed_at' => $area->last_refreshed_at?->toIso8601String(),
                'articles'     => \App\Support\Region::order($area->articles)->map(fn ($a) => $this->articleArray($a))->all(),
            ])->all();
    }

    /**
     * Articles across all feeds that match the user's watch keywords (Pro).
     */
    private function watchlist($user, $topicModels): array
    {
        if (! $user->isPro() || empty($user->watch_keywords)) {
            return [];
        }

        $hits = [];

        $scan = function (Topic $topic) use ($user, &$hits) {
            foreach ($topic->articles as $a) {
                $matches = $user->watchMatches($a->headline, $a->description);
                if (! empty($matches)) {
                    $hits[] = $this->articleArray($a) + [
                        'topic_name' => $topic->name,
                        'matches'    => $matches,
                    ];
                }
            }
        };

        foreach ($topicModels as $topic) {
            $scan($topic);
            foreach ($topic->children as $child) {
                $scan($child);
            }
        }

        // De-dupe the same story matched under multiple topics; cap the list.
        $unique = collect($hits)->unique('fingerprint')->values()->take(24)->all();

        return $unique;
    }

    private function articleArray(\App\Models\Article $a): array
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

    private function transform(Topic $topic, bool $withChildren = false): array
    {
        $data = [
            'id'                => $topic->id,
            'name'              => $topic->name,
            'parent_id'         => $topic->parent_id,
            'position'          => $topic->position,
            'mute_keywords'     => $topic->mute_keywords ?? [],
            'last_refreshed_at' => $topic->last_refreshed_at?->toIso8601String(),
            'articles'          => \App\Support\Region::order($topic->articles)->map(fn ($a) => $this->articleArray($a))->all(),
        ];

        if ($withChildren) {
            $data['children'] = $topic->children->map(fn ($c) => $this->transform($c))->all();
        }

        return $data;
    }
}
