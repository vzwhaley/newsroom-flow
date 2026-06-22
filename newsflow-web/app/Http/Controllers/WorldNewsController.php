<?php

namespace App\Http\Controllers;

use App\Services\Demo\WorldNewsFeed;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, clickable "World News" demo — a live example of exactly what a topic
 * feed looks like for a signed-in user. Pulls real, current headlines from free
 * RSS sources (no API key required), cached so the marketing page never hammers
 * the upstream feeds.
 */
class WorldNewsController extends Controller
{
    private const CACHE_KEY = 'demo:world-news';

    public function show(WorldNewsFeed $feed): Response
    {
        $articles = Cache::get(self::CACHE_KEY);

        if ($articles === null) {
            $articles = $feed->fetch((int) config('billing.articles_per_topic', 12));

            // Only cache a non-empty result, so a transient upstream failure
            // doesn't stick an empty feed for the next hour.
            if (! empty($articles)) {
                Cache::put(self::CACHE_KEY, $articles, now()->addHour());
            }
        }

        return Inertia::render('WorldNews', [
            'topic'    => 'World News',
            'articles' => $articles,
        ]);
    }
}
