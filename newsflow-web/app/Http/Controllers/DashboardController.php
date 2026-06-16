<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The user's personal newspaper: every followed topic with its current
     * 12-article feed.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $savedFingerprints = $user->savedArticles()->pluck('fingerprint')->all();

        $topics = $user->topics()
            ->with(['articles' => fn ($q) => $q->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->map(fn ($topic) => [
                'id'                => $topic->id,
                'name'              => $topic->name,
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
            ]);

        return Inertia::render('Dashboard', [
            'topics'            => $topics,
            'savedFingerprints' => $savedFingerprints,
        ]);
    }
}
