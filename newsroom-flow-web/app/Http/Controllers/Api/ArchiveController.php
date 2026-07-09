<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/archive — the user's history of rotated-out articles (Pro). Mirrors
 * the web ArchiveController: optional ?q= search, newest first, capped.
 */
class ArchiveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $locked = ! $user->isPro();
        $q = trim((string) $request->query('q', ''));

        if ($locked) {
            return response()->json(['locked' => true, 'q' => $q, 'articles' => []]);
        }

        $query = $user->archivedArticles();

        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w
                ->where('headline', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('source', 'like', $like)
                ->orWhere('topic_name', 'like', $like));
        }

        $articles = $query->limit(50)->get()->map(fn ($a) => [
            'id'          => $a->id,
            'headline'    => $a->headline,
            'description' => $a->description,
            'url'         => $a->url,
            'source'      => $a->source,
            'topic_name'  => $a->topic_name,
            'archived_at' => $a->archived_at?->toIso8601String(),
        ])->all();

        return response()->json(['locked' => false, 'q' => $q, 'articles' => $articles]);
    }
}
