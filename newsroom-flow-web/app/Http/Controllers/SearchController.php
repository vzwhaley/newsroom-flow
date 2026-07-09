<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\SavedArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Search across the user's current feeds and saved articles (Pro).
 */
class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        $locked = ! $user->isPro();

        $feed = [];
        $saved = [];

        if (! $locked && $q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

            $feed = Article::query()
                ->whereHas('topic', fn ($t) => $t->where('user_id', $user->id))
                ->where(fn ($w) => $w
                    ->where('headline', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('source', 'like', $like))
                ->with('topic:id,name')
                ->orderByDesc('published_at')
                ->limit(50)
                ->get()
                ->map(fn ($a) => [
                    'id'          => $a->id,
                    'headline'    => $a->headline,
                    'description' => $a->description,
                    'url'         => $a->url,
                    'source'      => $a->source,
                    'topic_name'  => $a->topic?->name,
                    'is_read'     => ! is_null($a->read_at),
                ])->all();

            $saved = SavedArticle::query()
                ->where('user_id', $user->id)
                ->where(fn ($w) => $w
                    ->where('headline', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('source', 'like', $like))
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn ($a) => [
                    'id'          => $a->id,
                    'headline'    => $a->headline,
                    'description' => $a->description,
                    'url'         => $a->url,
                    'source'      => $a->source,
                    'topic_name'  => $a->topic_name,
                ])->all();
        }

        return Inertia::render('Search', [
            'q'      => $q,
            'locked' => $locked,
            'feed'   => $feed,
            'saved'  => $saved,
        ]);
    }
}
