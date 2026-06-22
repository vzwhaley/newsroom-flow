<?php

namespace App\Services\Demo;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fetches real, current World News for the public /world-news demo from a few
 * reputable, free, keyless RSS feeds — so the marketing page shows genuine
 * headlines and links even before any paid news API key is configured.
 *
 * Aggregates multiple sources for variety + resilience, dedupes, and orders
 * newest-first. Network failures degrade gracefully (whatever succeeds is used).
 */
class WorldNewsFeed
{
    /** @var array<int, array{url: string, source: string}> */
    private const FEEDS = [
        ['url' => 'https://feeds.bbci.co.uk/news/world/rss.xml', 'source' => 'BBC News'],
        ['url' => 'https://feeds.npr.org/1004/rss.xml',          'source' => 'NPR'],
        ['url' => 'https://www.theguardian.com/world/rss',       'source' => 'The Guardian'],
    ];

    /**
     * @return array<int, array{headline: string, description: string, url: string, source: string, published_at: ?string}>
     */
    public function fetch(int $limit): array
    {
        $items = [];

        foreach (self::FEEDS as $feed) {
            try {
                $response = Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'NewsFlowBot/1.0 (+https://newsflow.app)'])
                    ->get($feed['url']);

                if ($response->ok()) {
                    $items = array_merge($items, $this->parse($response->body(), $feed['source']));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Dedupe by URL, newest-first, cap at the requested count.
        $byUrl = [];
        foreach ($items as $item) {
            $byUrl[$item['url']] = $item;
        }
        $unique = array_values($byUrl);

        usort($unique, fn ($a, $b) => ($b['published_at'] ?? '') <=> ($a['published_at'] ?? ''));

        return array_slice($unique, 0, $limit);
    }

    /**
     * @return array<int, array{headline: string, description: string, url: string, source: string, published_at: ?string}>
     */
    private function parse(string $xml, string $source): array
    {
        $previous = libxml_use_internal_errors(true);
        $rss = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($rss === false || ! isset($rss->channel->item)) {
            return [];
        }

        $out = [];

        foreach ($rss->channel->item as $item) {
            $headline = trim((string) $item->title);
            $url = trim((string) $item->link);

            if ($headline === '' || $url === '') {
                continue;
            }

            $description = trim(html_entity_decode(strip_tags((string) $item->description), ENT_QUOTES | ENT_HTML5));
            $publishedAt = null;
            if ((string) $item->pubDate !== '') {
                try {
                    $publishedAt = Carbon::parse((string) $item->pubDate)->toIso8601String();
                } catch (\Throwable $e) {
                    // leave null
                }
            }

            $out[] = [
                'headline'     => $headline,
                'description'  => Str::limit($description, 200),
                'url'          => $url,
                'source'       => $source,
                'published_at' => $publishedAt,
            ];
        }

        return $out;
    }
}
