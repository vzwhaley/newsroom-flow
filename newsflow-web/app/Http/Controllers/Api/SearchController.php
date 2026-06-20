<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\SavedArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/search?q= — search the user's feeds + saved articles (Pro).
 */
class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
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
                ->where(fn ($w) => $w->where('headline', 'like', $like)->orWhere('description', 'like', $like)->orWhere('source', 'like', $like))
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
                ->where(fn ($w) => $w->where('headline', 'like', $like)->orWhere('description', 'like', $like)->orWhere('source', 'like', $like))
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

        return response()->json([
            'locked' => $locked,
            'q'      => $q,
            'feed'   => $feed,
            'saved'  => $saved,
        ]);
    }
}
