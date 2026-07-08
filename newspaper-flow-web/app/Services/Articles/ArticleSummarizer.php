<?php

namespace App\Services\Articles;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * On-demand "TL;DR this" summaries (Pro). Fetches the full article, extracts
 * readable text, and asks Claude for a short summary. Best-effort: if the page
 * can't be fetched (paywall, bot block, timeout) it falls back to summarizing
 * the headline + description we already have, so the user always gets something.
 */
class ArticleSummarizer
{
    public function enabled(): bool
    {
        return (bool) config('newspaperflow.llm.enabled') && (bool) config('newspaperflow.llm.api_key');
    }

    /**
     * Return a TL;DR for the article, or null if summarization is unavailable.
     */
    public function summarize(Article $article): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $source = $this->fetchReadableText($article->url);

        // Fall back to what we already have if the page wasn't usable.
        if (Str::length($source) < 200) {
            $source = trim($article->headline."\n\n".$article->description);
        }

        return $this->askClaude($article->headline, $source);
    }

    /**
     * Fetch the article URL and reduce it to plain readable text.
     */
    private function fetchReadableText(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; NewsroomFlowBot/1.0; +https://newspaperflow.test)',
                'Accept'     => 'text/html',
            ])->timeout(12)->get($url);

            if (! $response->ok()) {
                return '';
            }

            $html = $response->body();

            // Drop scripts/styles/markup, decode entities, collapse whitespace.
            $html = preg_replace('#<(script|style|noscript|svg|head)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
            $text = strip_tags($html);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

            // Cap the amount of text we send to keep the call cheap and fast.
            return Str::limit($text, 6000, '');
        } catch (\Throwable $e) {
            Log::info('Article fetch for TL;DR failed', ['url' => $url, 'error' => $e->getMessage()]);

            return '';
        }
    }

    private function askClaude(string $headline, string $source): ?string
    {
        try {
            $prompt = "You are writing a TL;DR for a news reader who is deciding whether to open this "
                ."article. Summarize it in 2–3 short, neutral, factual sentences. No preamble, no "
                ."\"this article\", just the summary. Do not invent details that aren't in the text.\n\n"
                ."Headline: {$headline}\n\nArticle text:\n{$source}";

            $response = Http::withHeaders([
                'x-api-key'         => config('newspaperflow.llm.api_key'),
                'anthropic-version' => config('newspaperflow.llm.version', '2023-06-01'),
                'content-type'      => 'application/json',
            ])->timeout(30)->post(config('newspaperflow.llm.endpoint'), [
                'model'      => config('newspaperflow.llm.model'),
                'max_tokens' => 350,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (! $response->ok()) {
                Log::warning('Claude TL;DR call failed', ['status' => $response->status()]);

                return null;
            }

            $text = trim((string) $response->json('content.0.text', ''));

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Claude TL;DR exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
