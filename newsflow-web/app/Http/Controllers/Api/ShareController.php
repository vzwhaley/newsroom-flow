<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\SharedArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/articles/{article}/share — mint a branded share link for the
 * native apps' share sheets. Any tier: shared cards are marketing.
 */
class ShareController extends Controller
{
    public function store(Request $request, Article $article): JsonResponse
    {
        abort_unless($article->topic->user_id === $request->user()->id, 403);

        $share = SharedArticle::mint($request->user(), [
            'headline'    => $article->headline,
            'description' => $article->description,
            'url'         => $article->url,
            'source'      => $article->source,
            'image_url'   => $article->image_url,
            'topic_name'  => $article->topic->name,
        ]);

        return response()->json([
            'code' => $share->code,
            'url'  => $share->shareUrl(),
        ]);
    }
}
