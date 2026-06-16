<?php

namespace App\Http\Controllers;

use App\Models\Topic;
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

        $topics = $user->topLevelTopics()
            ->with([
                'articles' => fn ($q) => $q->orderBy('position'),
                'children' => fn ($q) => $q->orderBy('position'),
                'children.articles' => fn ($q) => $q->orderBy('position'),
            ])
            ->orderBy('position')
            ->get()
            ->map(fn ($topic) => $this->transform($topic, true))
            ->all();

        return Inertia::render('Dashboard', [
            'topics'            => $topics,
            'savedFingerprints' => $savedFingerprints,
        ]);
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
            'articles'          => $topic->articles->map(fn ($a) => [
                'id'           => $a->id,
                'headline'     => $a->headline,
                'description'  => $a->description,
                'url'          => $a->url,
                'source'       => $a->source,
                'image_url'    => $a->image_url,
                'fingerprint'  => $a->fingerprint,
                'published_at' => $a->published_at?->toIso8601String(),
            ])->all(),
        ];

        if ($withChildren) {
            $data['children'] = $topic->children->map(fn ($c) => $this->transform($c))->all();
        }

        return $data;
    }
}
