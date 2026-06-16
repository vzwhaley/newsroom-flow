<?php

namespace App\Services\Articles\Signals;

use App\Support\FetchedArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Approximates "most read" using Hacker News engagement.
 *
 * HN's Algolia API is keyless, free, commercial-clean and allows 10k req/hr,
 * so it's an ideal popularity signal. We make ONE call per topic, build a map
 * of story-URL → engagement (points + comments), and boost any candidate whose
 * canonical URL matches. Best-effort: any failure leaves candidates untouched.
 *
 * Note: HN skews tech/startup, so for general topics (sports, entertainment)
 * few candidates will match — that's fine; it only ever adds signal, never
 * removes coverage.
 */
class HackerNewsSignal
{
    private const ENDPOINT = 'https://hn.algolia.com/api/v1/search';

    /**
     * @param  array<int, FetchedArticle>  $candidates
     * @return array<int, FetchedArticle>
     */
    public function boost(string $topic, array $candidates): array
    {
        if (empty($candidates)) {
            return $candidates;
        }

        $engagementByFingerprint = $this->fetchEngagement($topic);

        if (empty($engagementByFingerprint)) {
            return $candidates;
        }

        foreach ($candidates as $candidate) {
            $fp = $candidate->fingerprint();

            if (isset($engagementByFingerprint[$fp])) {
                $candidate->popularityScore += $this->boostFor($engagementByFingerprint[$fp]);
            }
        }

        return $candidates;
    }

    /**
     * @return array<string, int>  fingerprint => engagement (points + comments)
     */
    private function fetchEngagement(string $topic): array
    {
        try {
            $response = Http::timeout(10)->get(self::ENDPOINT, [
                'query'       => $topic,
                'tags'        => 'story',
                'hitsPerPage' => 50,
            ]);

            if (! $response->ok()) {
                return [];
            }

            $map = [];

            foreach ($response->json('hits', []) as $hit) {
                $url = $hit['url'] ?? null;

                if (! $url) {
                    continue;
                }

                $fp = FetchedArticle::fingerprintForUrl($url);

                if ($fp === '') {
                    continue;
                }

                $engagement = (int) ($hit['points'] ?? 0) + (int) ($hit['num_comments'] ?? 0);

                // Keep the strongest engagement if a story appears twice.
                $map[$fp] = max($map[$fp] ?? 0, $engagement);
            }

            return $map;
        } catch (\Throwable $e) {
            Log::warning('Hacker News signal failed', ['topic' => $topic, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Diminishing-returns boost so a viral story lifts a candidate without
     * completely dominating the aggregator's own relevance score.
     */
    private function boostFor(int $engagement): float
    {
        if ($engagement <= 0) {
            return 0.0;
        }

        return round(min(50.0, log10($engagement + 1) * 18), 2);
    }
}
