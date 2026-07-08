<?php

namespace App\Services\Articles;

use App\Models\Topic;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI-powered local-outlet discovery. For a location not in the curated
 * directory, asks Claude — grounded in live web search — to identify the real
 * local news outlets that serve it, then validates the returned domains
 * (format + best-effort liveness, canonicalizing redirects so rebrands are
 * auto-corrected). This is the automated version of a manual "broadening run".
 *
 * Every layer degrades gracefully: no key / disabled / API error → returns an
 * empty list and the area falls back to the curated statewide/country sources.
 */
class LocalSourceDiscovery
{
    public function enabled(): bool
    {
        return (bool) config('newsflow.discovery.enabled')
            && (bool) config('newsflow.llm.api_key');
    }

    /**
     * Discover local outlets for an area's location.
     *
     * @return array{domains: array<int, string>, outlets: array<string, string>}
     */
    public function discover(Topic $area): array
    {
        $empty = ['domains' => [], 'outlets' => []];

        if (! $this->enabled()) {
            return $empty;
        }

        $place = $this->placeLabel($area);
        if ($place === '') {
            return $empty;
        }

        $raw = $this->askClaude($place, $area);
        if (! $raw) {
            return $empty;
        }

        [$domains, $outlets] = $this->parse($raw);
        $domains = $this->validate($domains);

        // Keep outlet names only for domains that survived validation.
        $outlets = array_intersect_key($outlets, array_flip($domains));

        $max = (int) config('newsflow.discovery.max_domains', 6);

        return [
            'domains' => array_slice($domains, 0, $max),
            'outlets' => $outlets,
        ];
    }

    private function placeLabel(Topic $area): string
    {
        $parts = array_filter([
            $area->locality,
            $area->region,
            $area->country_code === 'US' ? 'USA' : $area->country_code,
        ]);

        return trim(implode(', ', $parts));
    }

    private function askClaude(string $place, Topic $area): ?string
    {
        $prompt = <<<PROMPT
        You are building a directory of LOCAL news outlets. For the location below, use web search to identify the real news outlets that specifically serve this exact place, then return only their website domains.

        Location: {$place}

        Rules:
        - Include only outlets that genuinely cover THIS place: its local newspaper(s), its local TV news station(s), and its NPR/public-radio member station.
        - For a small town with no paper of its own, use its COUNTY newspaper plus the regional TV/NPR station that covers the area.
        - Return each outlet's CURRENT canonical website domain. If an outlet rebranded or moved domains, use the new one. Exclude outlets that have shut down or merged away.
        - Do NOT include national outlets (e.g. nytimes.com, cnn.com, usatoday.com) or aggregators/social sites.
        - Return 3 to 6 domains, most-local first.
        - Respond with ONLY a JSON object and nothing else:
          {"domains": ["example.com"], "outlets": {"example.com": "Example Outlet Name"}}
        PROMPT;

        try {
            $response = Http::timeout(60)->withHeaders([
                'x-api-key'         => config('newsflow.llm.api_key'),
                'anthropic-version' => config('newsflow.llm.version', '2023-06-01'),
                'content-type'      => 'application/json',
            ])->post(config('newsflow.llm.endpoint'), [
                'model'      => config('newsflow.discovery.model', 'claude-sonnet-5'),
                'max_tokens' => 1024,
                'tools'      => [[
                    'type'     => (string) config('newsflow.discovery.web_search_tool', 'web_search_20250305'),
                    'name'     => 'web_search',
                    'max_uses' => (int) config('newsflow.discovery.max_searches', 5),
                ]],
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (! $response->ok()) {
                Log::warning('Local-source discovery call failed', [
                    'place' => $place, 'status' => $response->status(),
                ]);

                return null;
            }

            // With the server-side web-search tool, the final answer is the
            // concatenation of the response's text blocks.
            $text = collect($response->json('content', []))
                ->where('type', 'text')
                ->pluck('text')
                ->implode("\n");

            return trim($text) ?: null;
        } catch (\Throwable $e) {
            Log::warning('Local-source discovery exception', ['place' => $place, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Parse the model's JSON into [domains, outlets]. Lenient: tolerates prose
     * or code fences around the JSON object.
     *
     * @return array{0: array<int, string>, 1: array<string, string>}
     */
    private function parse(string $raw): array
    {
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [[], []];
        }

        $domains = [];
        foreach ((array) ($decoded['domains'] ?? []) as $d) {
            $clean = $this->normalizeDomain((string) $d);
            if ($clean !== null) {
                $domains[] = $clean;
            }
        }

        $outlets = [];
        foreach ((array) ($decoded['outlets'] ?? []) as $domain => $name) {
            $clean = $this->normalizeDomain((string) $domain);
            if ($clean !== null && is_string($name)) {
                $outlets[$clean] = Str::limit(trim($name), 120, '');
            }
        }

        return [array_values(array_unique($domains)), $outlets];
    }

    /**
     * Reduce a raw string to a bare lowercase host, or null if implausible.
     */
    private function normalizeDomain(string $value): ?string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value; // strip path
        $value = preg_replace('#^www\.#', '', $value) ?? $value;
        $value = trim($value);

        // Plausible registrable domain: label(.label)+ with a 2+ char TLD.
        if (! preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+\.[a-z]{2,}$/', $value)
            && ! preg_match('/^[a-z0-9-]+\.[a-z]{2,}$/', $value)) {
            return null;
        }

        // Never allow national/aggregator giants to sneak in as "local".
        return in_array($value, self::BLOCKLIST, true) ? null : $value;
    }

    /**
     * Best-effort liveness validation. Drops unreachable domains and, when a
     * domain redirects to a different host, adopts the canonical host — the
     * automated equivalent of catching a rebrand (ktuu.com → alaskasnews...).
     *
     * @param  array<int, string>  $domains
     * @return array<int, string>
     */
    private function validate(array $domains): array
    {
        if (! config('newsflow.discovery.validate_liveness', true)) {
            return $domains;
        }

        $out = [];
        foreach ($domains as $domain) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NewsroomFlowBot/1.0; +https://newsflow.app)'])
                    ->get("https://{$domain}/");

                if (! $response->successful() && ! $response->redirect()) {
                    continue; // dead / error
                }

                // Follow to the final host in case of a cross-host rebrand redirect.
                $finalHost = parse_url((string) $response->effectiveUri(), PHP_URL_HOST);
                $canonical = $this->normalizeDomain((string) $finalHost) ?? $domain;

                $out[] = $canonical;
            } catch (\Throwable $e) {
                // Unreachable — skip it.
                continue;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * National outlets / aggregators that must never be biased toward as
     * "local". Not exhaustive — just the obvious ones the model might return.
     */
    private const BLOCKLIST = [
        'nytimes.com', 'washingtonpost.com', 'wsj.com', 'usatoday.com',
        'cnn.com', 'foxnews.com', 'nbcnews.com', 'abcnews.go.com', 'cbsnews.com',
        'apnews.com', 'reuters.com', 'npr.org', 'bbc.com', 'bbc.co.uk',
        'theguardian.com', 'google.com', 'news.google.com', 'facebook.com',
        'x.com', 'twitter.com', 'reddit.com', 'yahoo.com', 'msn.com',
        'newsbreak.com', 'patch.com',
    ];
}
