<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ReadingDay;
use App\Services\Articles\ArticleSummarizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Per-article actions invoked over XHR (not Inertia visits): on-demand TL;DR
 * summaries (Pro) and read/unread tracking.
 */
class ArticleController extends Controller
{
    /**
     * POST /articles/{article}/summary — generate (or return cached) TL;DR.
     */
    public function summary(Request $request, Article $article, ArticleSummarizer $summarizer): JsonResponse
    {
        $this->authorizeArticle($request, $article);

        if (! $request->user()->isPro()) {
            return response()->json([
                'error' => 'TL;DR summaries are a Pro feature.',
            ], 403);
        }

        // Return the cached summary if we already have one.
        if (filled($article->tldr)) {
            return response()->json(['tldr' => $article->tldr, 'cached' => true]);
        }

        if (! $summarizer->enabled()) {
            return response()->json([
                'error' => 'Summaries aren’t available right now.',
            ], 503);
        }

        $tldr = $summarizer->summarize($article);

        if (! $tldr) {
            return response()->json([
                'error' => 'Couldn’t summarize this article — try opening it instead.',
            ], 422);
        }

        $article->forceFill(['tldr' => $tldr])->save();

        return response()->json(['tldr' => $tldr, 'cached' => false]);
    }

    /**
     * POST /articles/{article}/read — mark read.
     */
    public function markRead(Request $request, Article $article): JsonResponse
    {
        $this->authorizeArticle($request, $article);

        if (is_null($article->read_at)) {
            $article->forceFill(['read_at' => now()])->save();
        }

        // Every open counts as reading activity for the day, so the streak
        // reflects that the user read today even when re-opening an article
        // (feeds don't always surface a brand-new story to read each day).
        ReadingDay::bump($request->user());

        return response()->json(['read_at' => $article->read_at?->toIso8601String()]);
    }

    /**
     * DELETE /articles/{article}/read — mark unread.
     */
    public function markUnread(Request $request, Article $article): JsonResponse
    {
        $this->authorizeArticle($request, $article);

        $article->forceFill(['read_at' => null])->save();

        return response()->json(['read_at' => null]);
    }

    private function authorizeArticle(Request $request, Article $article): void
    {
        abort_unless($article->topic->user_id === $request->user()->id, 403);
    }
}
