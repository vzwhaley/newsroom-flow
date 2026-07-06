<?php

namespace App\Services\Articles;

use App\Contracts\ArticleProvider;
use App\Contracts\LocationAwareProvider;
use App\Services\Articles\Signals\HackerNewsSignal;
use App\Support\FetchedArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        // Free keyless baseline — real articles with zero configuration.
        if (config('newsflow.sources.google_news.enabled')) {
            $all = array_merge($all, $this->fromGoogleNewsRss($topic, $pool));
        }

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
        return (bool) (config('newsflow.sources.google_news.enabled')
            || config('newsflow.sources.thenewsapi.key')
            || config('newsflow.sources.newsdata.key')
            || config('newsflow.sources.gnews.key')
            || config('newsflow.sources.newsapi.key'));
    }

    /** Registrable-ish publisher name from a URL host (fallback source label). */
    private function sourceFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host ? preg_replace('/^www\./', '', strtolower($host)) : null;
    }

    /**
     * Google News RSS — free, keyless, no signup. Scrapes the public RSS search
     * feed, which returns real, live articles for any query (topics) and any
     * place (local areas, via the geo country). Titles come as "Headline -
     * Publisher"; we split off the publisher and clean the HTML snippet.
     */
    private function fromGoogleNewsRss(string $topic, int $pool): array
    {
        try {
            // Locale: default US/English; scope to the area's country when set.
            $gl = $this->geo['country'] ? strtoupper($this->geo['country']) : 'US';
            $params = [
                'q'    => $topic,
                'hl'   => 'en-'.$gl,
                'gl'   => $gl,
                'ceid' => $gl.':en',
            ];

            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NewsFlow/1.0)'])
                ->get(config('newsflow.sources.google_news.endpoint'), $params);

            if (! $response->ok()) {
                return [];
            }

            return $this->parseRss($response->body(), $pool);
        } catch (\Throwable $e) {
            Log::warning('Google News RSS fetch failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Parse an RSS 2.0 document into FetchedArticle candidates.
     *
     * @return array<int, FetchedArticle>
     */
    private function parseRss(string $xml, int $limit): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        if ($doc === false || ! isset($doc->channel->item)) {
            return [];
        }

        $out = [];

        foreach ($doc->channel->item as $item) {
            if (count($out) >= $limit) {
                break;
            }

            $title = trim((string) $item->title);
            $url = trim((string) $item->link);

            if ($title === '' || $url === '') {
                continue;
            }

            $source = isset($item->source) ? trim((string) $item->source) : null;

            // Google News formats titles as "Headline - Publisher"; drop the
            // trailing publisher so the headline reads cleanly.
            if ($source !== null && $source !== '' && str_ends_with($title, ' - '.$source)) {
                $title = trim(substr($title, 0, -\strlen(' - '.$source)));
            }

            // Bing items often omit <source>; derive it from the publisher host.
            if ($source === null || $source === '') {
                $source = $this->sourceFromUrl($url);
            }

            // The RSS description is HTML (often a related-coverage list); strip
            // to plain text and keep it short.
            $description = trim(preg_replace('/\s+/', ' ', html_entity_decode(
                strip_tags((string) $item->description),
                ENT_QUOTES | ENT_HTML5,
            )));
            $description = Str::limit($description, 220);

            $publishedAt = null;
            if (! empty($item->pubDate)) {
                try {
                    $publishedAt = Carbon::parse((string) $item->pubDate);
                } catch (\Throwable) {
                    $publishedAt = null;
                }
            }

            $out[] = new FetchedArticle(
                headline: $title,
                description: $description,
                url: $url,
                source: $source,
                imageUrl: null,
                publishedAt: $publishedAt,
                popularityScore: 50.0,
            );
        }

        return $out;
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
        // Pass 1: collapse exact-URL duplicates (same link seen more than once).
        $byUrl = [];
        foreach ($candidates as $c) {
            $fp = $c->fingerprint();
            $byUrl[$fp] = isset($byUrl[$fp]) ? $this->mergeDuplicates($byUrl[$fp], $c) : $c;
        }

        // Pass 2: collapse the SAME story across different sources by normalized
        // headline (their URLs differ — e.g. a Google redirect vs a Bing direct
        // link). Merging lets us keep, say, GDELT's image and Google's summary
        // on one article, and prefer a direct publisher URL over an aggregator.
        $byHeadline = [];
        $noKey = [];
        foreach ($byUrl as $c) {
            $key = $this->headlineKey($c->headline);
            // Only merge on substantial headlines; short/generic ones ("News
            // roundup") could collide across genuinely different stories.
            if (\strlen($key) < 20) {
                $noKey[] = $c;
                continue;
            }
            $byHeadline[$key] = isset($byHeadline[$key]) ? $this->mergeDuplicates($byHeadline[$key], $c) : $c;
        }

        return array_merge(array_values($byHeadline), $noKey);
    }

    /** Normalized headline key for cross-source dedupe (alphanumeric, lowercase). */
    private function headlineKey(string $headline): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', mb_strtolower($headline));
    }

    /** Aggregator links (Google/Bing redirects) are worse than direct publisher URLs. */
    private function isAggregatorUrl(string $url): bool
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return str_contains($host, 'news.google.com') || str_contains($host, 'bing.com');
    }

    /**
     * Merge two representations of the same story into one, keeping the better
     * link (direct publisher over aggregator, then higher score) and filling any
     * missing fields (image, description, source, date) from the other.
     */
    private function mergeDuplicates(FetchedArticle $a, FetchedArticle $b): FetchedArticle
    {
        $aAgg = $this->isAggregatorUrl($a->url);
        $bAgg = $this->isAggregatorUrl($b->url);

        if ($aAgg !== $bAgg) {
            [$primary, $other] = $aAgg ? [$b, $a] : [$a, $b]; // prefer the direct URL
        } else {
            [$primary, $other] = $b->popularityScore > $a->popularityScore ? [$b, $a] : [$a, $b];
        }

        $primary->imageUrl = $primary->imageUrl ?: $other->imageUrl;
        if (trim($primary->description) === '') {
            $primary->description = $other->description;
        }
        $primary->source = $primary->source ?: $other->source;
        $primary->publishedAt = $primary->publishedAt ?: $other->publishedAt;
        $primary->popularityScore = max($primary->popularityScore, $other->popularityScore);

        return $primary;
    }
}
