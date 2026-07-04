<?php

namespace App\Services\Articles;

use App\Contracts\ArticleProvider;
use App\Contracts\LocationAwareProvider;
use App\Services\Articles\Signals\HackerNewsSignal;
use App\Support\FetchedArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The recommended provider. Blends three layers:
 *
 *   1. Fresh coverage  — news aggregator APIs (NewsAPI / GNews / NewsData).
 *   2. Popularity       — public engagement signals (Reddit, Hacker News)
 *                         used to approximate "most read / most popular",
 *                         since true page-view counts are private.
 *   3. Summarize/dedupe — an LLM (Claude) rewrites the headline + short
 *                         description, removes near-duplicate stories, and
 *                         helps fill niche topics.
 *
 * Every layer is optional and degrades gracefully: if a source has no key
 * configured it is skipped, and if NO real sources are configured at all the
 * provider falls back to the StubArticleProvider so the app always returns a
 * full feed during local development.
 */
class HybridArticleProvider implements ArticleProvider, LocationAwareProvider
{
    /**
     * Per-fetch geographic context (set by fetchLocal, cleared after). Threaded
     * into each source request to scope results to a place.
     *
     * @var array{country:?string, domains:array<string>}
     */
    private array $geo = ['country' => null, 'domains' => []];

    public function __construct(
        private readonly StubArticleProvider $stub,
        private readonly HackerNewsSignal $hackerNews,
    ) {
    }

    public function fetch(string $topic, int $count, array $excludeFingerprints = []): array
    {
        $pool = (int) config('newsflow.candidate_pool', 40);

        $candidates = $this->gatherFromSources($topic, $pool);

        // No real news sources configured yet → use the stub so the product
        // is fully usable before API keys are added.
        if (empty($candidates) && ! $this->anySourceConfigured()) {
            return $this->stub->fetch($topic, $count, $excludeFingerprints);
        }

        $candidates = $this->dedupe($candidates);
        $candidates = $this->applyPopularitySignals($topic, $candidates);

        // Rank: most popular first, then most recent.
        usort($candidates, function (FetchedArticle $a, FetchedArticle $b) {
            if ($a->popularityScore === $b->popularityScore) {
                return ($b->publishedAt?->timestamp ?? 0) <=> ($a->publishedAt?->timestamp ?? 0);
            }

            return $b->popularityScore <=> $a->popularityScore;
        });

        // Prefer genuinely new stories; keep the rest as backfill so we can
        // still guarantee a full set on quiet days.
        $excluded = array_flip($excludeFingerprints);
        $fresh = [];
        $seen = [];
        foreach ($candidates as $c) {
            if (! isset($excluded[$c->fingerprint()])) {
                $fresh[] = $c;
            } else {
                $seen[] = $c;
            }
        }
        $ordered = array_merge($fresh, $seen);

        // Summarize only the final set we'll actually return — cheaper.
        $final = array_slice($ordered, 0, $count);

        return $this->summarizeWithLlm($topic, $final);
    }

    /**
     * Local-area fetch: same pipeline, scoped to a country and biased toward
     * curated local outlets (precision layers 1 + 2). The geo context is
     * threaded into every source request, then cleared.
     */
    public function fetchLocal(
        string $query,
        int $count,
        array $excludeFingerprints = [],
        ?string $country = null,
        array $domains = [],
    ): array {
        $this->geo = ['country' => $country, 'domains' => $domains];

        try {
            return $this->fetch($query, $count, $excludeFingerprints);
        } finally {
            $this->geo = ['country' => null, 'domains' => []];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Layer 1 — fresh coverage from news aggregator APIs
    |--------------------------------------------------------------------------
    */

    private function gatherFromSources(string $topic, int $pool): array
    {
        $all = [];

        if ($key = config('newsflow.sources.thenewsapi.key')) {
            $all = array_merge($all, $this->fromTheNewsApi($topic, $pool, $key));
        }

        if ($key = config('newsflow.sources.newsdata.key')) {
            $all = array_merge($all, $this->fromNewsData($topic, $key));
        }

        if ($key = config('newsflow.sources.gnews.key')) {
            $all = array_merge($all, $this->fromGNews($topic, $pool, $key));
        }

        if ($key = config('newsflow.sources.newsapi.key')) {
            $all = array_merge($all, $this->fromNewsApi($topic, $pool, $key));
        }

        return $all;
    }

    private function anySourceConfigured(): bool
    {
        return (bool) (config('newsflow.sources.thenewsapi.key')
            || config('newsflow.sources.newsdata.key')
            || config('newsflow.sources.gnews.key')
            || config('newsflow.sources.newsapi.key'));
    }

    private function fromTheNewsApi(string $topic, int $pool, string $key): array
    {
        try {
            $params = [
                'api_token'       => $key,
                'search'          => $topic,
                'language'        => 'en',
                'sort'            => 'relevance_score',
                'published_after' => Carbon::yesterday()->toDateString(),
                'limit'           => min($pool, 100),
            ];
            if ($this->geo['country']) {
                $params['locale'] = $this->geo['country'];
            }
            if ($this->geo['domains']) {
                $params['domains'] = implode(',', $this->geo['domains']);
            }

            $response = Http::timeout(15)->get(config('newsflow.sources.thenewsapi.endpoint'), $params);

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('data', []))
                ->map(fn ($a) => new FetchedArticle(
                    headline: (string) ($a['title'] ?? ''),
                    description: (string) ($a['description'] ?? $a['snippet'] ?? ''),
                    url: (string) ($a['url'] ?? ''),
                    source: $a['source'] ?? null,
                    imageUrl: $a['image_url'] ?? null,
                    publishedAt: isset($a['published_at']) ? Carbon::parse($a['published_at']) : null,
                    popularityScore: 50.0,
                ))
                ->filter(fn (FetchedArticle $a) => $a->headline !== '' && $a->url !== '')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('TheNewsAPI fetch failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return [];
        }
    }

    private function fromNewsApi(string $topic, int $pool, string $key): array
    {
        try {
            $params = [
                'q'        => $topic,
                'from'     => Carbon::yesterday()->toDateString(),
                'sortBy'   => 'popularity',
                'language' => 'en',
                'pageSize' => min($pool, 100),
                'apiKey'   => $key,
            ];
            // NewsAPI /everything has no country filter; bias by domain instead.
            if ($this->geo['domains']) {
                $params['domains'] = implode(',', $this->geo['domains']);
            }

            $response = Http::timeout(15)->get(config('newsflow.sources.newsapi.endpoint'), $params);

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('articles', []))
                ->map(fn ($a) => new FetchedArticle(
                    headline: (string) ($a['title'] ?? ''),
                    description: (string) ($a['description'] ?? $a['content'] ?? ''),
                    url: (string) ($a['url'] ?? ''),
                    source: $a['source']['name'] ?? null,
                    imageUrl: $a['urlToImage'] ?? null,
                    publishedAt: isset($a['publishedAt']) ? Carbon::parse($a['publishedAt']) : null,
                    popularityScore: 50.0, // refined by popularity-signal layer
                ))
                ->filter(fn (FetchedArticle $a) => $a->headline !== '' && $a->url !== '')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('NewsAPI fetch failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return [];
        }
    }

    private function fromGNews(string $topic, int $pool, string $key): array
    {
        try {
            $params = [
                'q'      => $topic,
                'lang'   => 'en',
                'max'    => min($pool, 100),
                'from'   => Carbon::yesterday()->toIso8601String(),
                'sortby' => 'relevance',
                'apikey' => $key,
            ];
            // GNews supports country scoping (no domain filter).
            if ($this->geo['country']) {
                $params['country'] = $this->geo['country'];
            }

            $response = Http::timeout(15)->get(config('newsflow.sources.gnews.endpoint'), $params);

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('articles', []))
                ->map(fn ($a) => new FetchedArticle(
                    headline: (string) ($a['title'] ?? ''),
                    description: (string) ($a['description'] ?? ''),
                    url: (string) ($a['url'] ?? ''),
                    source: $a['source']['name'] ?? null,
                    imageUrl: $a['image'] ?? null,
                    publishedAt: isset($a['publishedAt']) ? Carbon::parse($a['publishedAt']) : null,
                    popularityScore: 50.0,
                ))
                ->filter(fn (FetchedArticle $a) => $a->headline !== '' && $a->url !== '')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('GNews fetch failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return [];
        }
    }

    private function fromNewsData(string $topic, string $key): array
    {
        try {
            $params = [
                'apikey'   => $key,
                'q'        => $topic,
                'language' => 'en',
            ];
            // NewsData.io supports both country and domain scoping.
            if ($this->geo['country']) {
                $params['country'] = $this->geo['country'];
            }
            if ($this->geo['domains']) {
                $params['domain'] = implode(',', array_map(
                    fn ($d) => explode('.', $d)[0], // NewsData wants the id, not the FQDN
                    $this->geo['domains'],
                ));
            }

            $response = Http::timeout(15)->get(config('newsflow.sources.newsdata.endpoint'), $params);

            if (! $response->ok()) {
                return [];
            }

            return collect($response->json('results', []))
                ->map(fn ($a) => new FetchedArticle(
                    headline: (string) ($a['title'] ?? ''),
                    description: (string) ($a['description'] ?? ''),
                    url: (string) ($a['link'] ?? ''),
                    source: $a['source_id'] ?? null,
                    imageUrl: $a['image_url'] ?? null,
                    publishedAt: isset($a['pubDate']) ? Carbon::parse($a['pubDate']) : null,
                    popularityScore: 50.0,
                ))
                ->filter(fn (FetchedArticle $a) => $a->headline !== '' && $a->url !== '')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('NewsData fetch failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Layer 2 — popularity signals
    |--------------------------------------------------------------------------
    |
    | Approximates "most read" using public engagement. We boost a candidate
    | when its URL surfaces on Reddit / Hacker News with high score/comments.
    | Kept intentionally lightweight; expand with more signals over time.
    |
    */

    private function applyPopularitySignals(string $topic, array $candidates): array
    {
        // Hacker News (keyless, free) — boost candidates that are getting real
        // engagement. One call per topic; best-effort, never throws.
        if (config('newsflow.signals.hacker_news')) {
            $candidates = $this->hackerNews->boost($topic, $candidates);
        }

        // Future: Bluesky firehose link-mention counts as a second free signal
        // (see config newsflow.signals.bluesky). Reddit is intentionally left
        // out — its 2025+ terms require a paid licence for commercial use.

        return $candidates;
    }

    /*
    |--------------------------------------------------------------------------
    | Layer 3 — LLM summarize / dedupe
    |--------------------------------------------------------------------------
    */

    private function summarizeWithLlm(string $topic, array $candidates): array
    {
        if (! config('newsflow.llm.enabled') || empty($candidates)) {
            return $candidates;
        }

        $apiKey = config('newsflow.llm.api_key');
        if (! $apiKey) {
            return $candidates;
        }

        // Best-effort: ask Claude (Haiku) to tighten each description to one
        // crisp sentence. If anything goes wrong we keep the originals — the
        // feed must never break because of the summarizer.
        try {
            $list = [];
            foreach ($candidates as $i => $c) {
                $list[] = "[{$i}] ".$c->headline.' — '.mb_substr($c->description, 0, 400);
            }

            $prompt = "For each numbered news item below on the topic \"{$topic}\", write ONE crisp, "
                ."neutral sentence (max 30 words) summarizing it for a headline reader. Do NOT add "
                ."facts not present. Respond ONLY with a JSON array of objects like "
                .'[{"i":0,"summary":"..."}], one per item, same indices.'."\n\n".implode("\n", $list);

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => config('newsflow.llm.version', '2023-06-01'),
                    'content-type'      => 'application/json',
                ])
                ->post(config('newsflow.llm.endpoint'), [
                    'model'      => config('newsflow.llm.model'),
                    'max_tokens' => 1500,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (! $response->ok()) {
                return $candidates;
            }

            $text = $response->json('content.0.text', '');
            $summaries = $this->parseSummaries($text);

            foreach ($summaries as $i => $summary) {
                if (isset($candidates[$i]) && is_string($summary) && trim($summary) !== '') {
                    $candidates[$i]->description = trim($summary);
                }
            }

            return $candidates;
        } catch (\Throwable $e) {
            Log::warning('LLM summarize failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return $candidates;
        }
    }

    /**
     * Parse the model's JSON array of {i, summary} into [index => summary].
     *
     * @return array<int, string>
     */
    private function parseSummaries(string $text): array
    {
        // Be lenient: the model may wrap the JSON in prose or code fences.
        if (preg_match('/\[.*\]/s', $text, $m)) {
            $text = $m[0];
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $row) {
            if (isset($row['i'], $row['summary'])) {
                $out[(int) $row['i']] = (string) $row['summary'];
            }
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function dedupe(array $candidates): array
    {
        $byFingerprint = [];

        foreach ($candidates as $c) {
            $fp = $c->fingerprint();

            // Keep the higher-scored instance of a duplicated story.
            if (! isset($byFingerprint[$fp]) || $c->popularityScore > $byFingerprint[$fp]->popularityScore) {
                $byFingerprint[$fp] = $c;
            }
        }

        return array_values($byFingerprint);
    }
}
