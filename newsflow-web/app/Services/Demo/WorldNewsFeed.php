<?php

namespace App\Services\Demo;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fetches real, current World News for the public /world-news demo from a wide
 * set of reputable, free, keyless RSS feeds — so the marketing page shows
 * genuine headlines and links even before any paid news API key is configured.
 *
 * To keep the demo varied, it takes just the single newest story from EACH
 * publisher (so every card is a different source), then orders them by region —
 * American outlets first, then European, then Asian — newest-first within each.
 * Feeds are fetched concurrently; failures are skipped.
 */
class WorldNewsFeed
{
    /** @var array<int, array{url: string, source: string, region: string}> */
    private const FEEDS = [
        // American
        ['url' => 'https://feeds.npr.org/1004/rss.xml',                 'source' => 'NPR',                 'region' => 'american'],
        ['url' => 'https://www.cbsnews.com/latest/rss/world',           'source' => 'CBS News',            'region' => 'american'],
        ['url' => 'https://moxie.foxnews.com/google-publisher/world.xml', 'source' => 'Fox News',          'region' => 'american'],
        ['url' => 'https://www.pbs.org/newshour/feeds/rss/world',       'source' => 'PBS NewsHour',        'region' => 'american'],
        ['url' => 'https://www.cbc.ca/webfeed/rss/rss-world',           'source' => 'CBC',                 'region' => 'american'],

        // European
        ['url' => 'https://feeds.bbci.co.uk/news/world/rss.xml',        'source' => 'BBC News',            'region' => 'european'],
        ['url' => 'https://www.theguardian.com/world/rss',             'source' => 'The Guardian',        'region' => 'european'],
        ['url' => 'https://www.independent.co.uk/news/world/rss',      'source' => 'The Independent',     'region' => 'european'],
        ['url' => 'https://feeds.skynews.com/feeds/rss/world.xml',     'source' => 'Sky News',            'region' => 'european'],
        ['url' => 'https://rss.dw.com/xml/rss-en-world',               'source' => 'Deutsche Welle',      'region' => 'european'],
        ['url' => 'https://www.france24.com/en/rss',                   'source' => 'France 24',           'region' => 'european'],

        // Asian (incl. Middle East)
        ['url' => 'https://timesofindia.indiatimes.com/rssfeeds/296589292.cms', 'source' => 'Times of India', 'region' => 'asian'],
        ['url' => 'https://www.aljazeera.com/xml/rss/all.xml',         'source' => 'Al Jazeera',          'region' => 'asian'],
        ['url' => 'https://www.timesofisrael.com/feed/',              'source' => 'The Times of Israel', 'region' => 'asian'],
    ];

    /** Region display order: lower = shown first. */
    private const REGION_PRIORITY = ['american' => 0, 'european' => 1, 'asian' => 2];

    /**
     * @return array<int, array{headline: string, description: string, url: string, source: string, published_at: ?string}>
     */
    public function fetch(int $limit): array
    {
        $responses = $this->fetchAll();

        $picked = [];

        foreach (self::FEEDS as $feed) {
            $response = $responses[$feed['source']] ?? null;

            if (! $response instanceof Response || ! $response->ok()) {
                continue;
            }

            $items = $this->parse($response->body(), $feed['source']);
            if (empty($items)) {
                continue;
            }

            // Newest story from this publisher only — one per source.
            usort($items, fn ($a, $b) => ($b['published_at'] ?? '') <=> ($a['published_at'] ?? ''));
            $article = $items[0];
            $article['region'] = $feed['region'];
            $picked[] = $article;
        }

        // Order by region (American → European → Asian), newest-first within each.
        usort($picked, function ($a, $b) {
            $pa = self::REGION_PRIORITY[$a['region']] ?? 9;
            $pb = self::REGION_PRIORITY[$b['region']] ?? 9;

            return $pa !== $pb
                ? $pa <=> $pb
                : (($b['published_at'] ?? '') <=> ($a['published_at'] ?? ''));
        });

        // Cap, then drop the internal region key from the payload.
        return array_map(function ($a) {
            unset($a['region']);

            return $a;
        }, array_slice($picked, 0, $limit));
    }

    /**
     * Fetch every feed concurrently, keyed by source name.
     *
     * @return array<string, mixed>
     */
    private function fetchAll(): array
    {
        try {
            return Http::pool(fn (Pool $pool) => collect(self::FEEDS)
                ->map(fn ($feed) => $pool
                    ->as($feed['source'])
                    ->timeout(5)
                    ->withHeaders(['User-Agent' => 'NewsFlowBot/1.0 (+https://newsflow.app)'])
                    ->get($feed['url']))
                ->all());
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
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
