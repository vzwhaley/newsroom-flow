<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ReadingDay;
use App\Services\Articles\ArticleSummarizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * POST /api/articles/{article}/read
     */
    public function markRead(Request $request, Article $article): JsonResponse
    {
        $this->authorizeArticle($request, $article);

        if (is_null($article->read_at)) {
            $article->forceFill(['read_at' => now()])->save();
        }

        // Every open counts as reading activity for the day (keeps the streak
        // alive even when re-opening an already-read article).
        ReadingDay::bump($request->user());

        return response()->json(['is_read' => true]);
    }

    /**
     * DELETE /api/articles/{article}/read
     */
    public function markUnread(Request $request, Article $article): JsonResponse
    {
        $this->authorizeArticle($request, $article);
        $article->forceFill(['read_at' => null])->save();

        return response()->json(['is_read' => false]);
    }

    /**
     * POST /api/articles/{article}/summary — on-demand TL;DR (Pro).
     */
    public function summary(Request $request, Article $article, ArticleSummarizer $summarizer): JsonResponse
    {
        $this->authorizeArticle($request, $article);

        if (! $request->user()->isPro()) {
            return response()->json(['message' => 'TL;DR summaries are a Pro feature.'], 403);
        }

        if (filled($article->tldr)) {
            return response()->json(['tldr' => $article->tldr, 'cached' => true]);
        }

        if (! $summarizer->enabled()) {
            return response()->json(['message' => 'Summaries aren’t available right now.'], 503);
        }

        $tldr = $summarizer->summarize($article);

        if (! $tldr) {
            return response()->json(['message' => 'Couldn’t summarize this article.'], 422);
        }

        $article->forceFill(['tldr' => $tldr])->save();

        return response()->json(['tldr' => $tldr, 'cached' => false]);
    }

    private function authorizeArticle(Request $request, Article $article): void
    {
        abort_unless($article->topic->user_id === $request->user()->id, 403);
    }
}
