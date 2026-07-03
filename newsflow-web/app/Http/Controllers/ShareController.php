<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\SharedArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Branded article share links. Minting is authenticated (any tier — shares
 * are marketing); the landing page /s/{code} is public and server-rendered
 * so every scraper gets proper Open Graph tags.
 */
class ShareController extends Controller
{
    /**
     * POST /articles/{article}/share — mint (or reuse) a share link.
     */
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

    /**
     * GET /s/{code} — the public share card. Plain server-rendered blade
     * (not Inertia) so JS-less scrapers read the article's OG tags.
     */
    public function show(string $code): View
    {
        $share = SharedArticle::where('code', $code)->firstOrFail();

        $share->increment('clicks');

        return view('share', ['share' => $share]);
    }
}
