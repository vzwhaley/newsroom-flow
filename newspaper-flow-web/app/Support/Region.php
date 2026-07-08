<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Classifies a news article's publisher into a display region so feeds can be
 * ordered American → European → Asian (everything else last). Detection is a
 * best-effort blend of a curated publisher-domain map, a source-name map, and a
 * country-code-TLD fallback — unknown publishers sort last.
 */
class Region
{
    /** Lower = shown first. */
    private const RANK = ['american' => 0, 'european' => 1, 'asian' => 2, 'other' => 3];

    /** Curated host substrings → region (most reliable signal). */
    private const DOMAINS = [
        // American (US + Canada)
        'cnn.com' => 'american', 'foxnews.com' => 'american', 'nytimes.com' => 'american',
        'washingtonpost.com' => 'american', 'npr.org' => 'american', 'cbsnews.com' => 'american',
        'nbcnews.com' => 'american', 'abcnews.go.com' => 'american', 'usatoday.com' => 'american',
        'wsj.com' => 'american', 'bloomberg.com' => 'american', 'apnews.com' => 'american',
        'politico.com' => 'american', 'axios.com' => 'american', 'thehill.com' => 'american',
        'pbs.org' => 'american', 'latimes.com' => 'american', 'nypost.com' => 'american',
        'newsweek.com' => 'american', 'time.com' => 'american', 'vox.com' => 'american',
        'businessinsider.com' => 'american', 'huffpost.com' => 'american', 'cnbc.com' => 'american',
        'cbc.ca' => 'american', 'ctvnews.ca' => 'american', 'globalnews.ca' => 'american',
        'theglobeandmail.com' => 'american', 'nationalpost.com' => 'american',

        // European (UK + EU)
        'bbc.co.uk' => 'european', 'bbc.com' => 'european', 'theguardian.com' => 'european',
        'independent.co.uk' => 'european', 'telegraph.co.uk' => 'european', 'ft.com' => 'european',
        'skynews.com' => 'european', 'sky.com' => 'european', 'dailymail.co.uk' => 'european',
        'mirror.co.uk' => 'european', 'reuters.com' => 'european', 'economist.com' => 'european',
        'dw.com' => 'european', 'france24.com' => 'european', 'euronews.com' => 'european',
        'lemonde.fr' => 'european', 'spiegel.de' => 'european', 'irishtimes.com' => 'european',
        'rte.ie' => 'european', 'elpais.com' => 'european', 'politico.eu' => 'european',
        'thelocal' => 'european', 'rfi.fr' => 'european', 'metro.co.uk' => 'european',

        // Asian (incl. Middle East)
        'aljazeera.com' => 'asian', 'indiatimes.com' => 'asian', 'hindustantimes.com' => 'asian',
        'thehindu.com' => 'asian', 'ndtv.com' => 'asian', 'scmp.com' => 'asian',
        'japantimes.co.jp' => 'asian', 'straitstimes.com' => 'asian', 'channelnewsasia.com' => 'asian',
        'koreaherald.com' => 'asian', 'koreatimes.co.kr' => 'asian', 'timesofisrael.com' => 'asian',
        'jpost.com' => 'asian', 'haaretz.com' => 'asian', 'arabnews.com' => 'asian',
        'gulfnews.com' => 'asian', 'thenationalnews.com' => 'asian', 'dawn.com' => 'asian',
        'nikkei.com' => 'asian', 'asia.nikkei.com' => 'asian', 'globaltimes.cn' => 'asian',
    ];

    /** Source-name substrings (lowercased) → region, when the host is unknown. */
    private const SOURCE_KEYWORDS = [
        'npr' => 'american', 'cnn' => 'american', 'fox news' => 'american', 'cbs' => 'american',
        'nbc' => 'american', 'abc news' => 'american', 'new york times' => 'american',
        'washington post' => 'american', 'associated press' => 'american', 'bloomberg' => 'american',
        'pbs' => 'american', 'usa today' => 'american', 'wall street journal' => 'american',
        'cbc' => 'american', 'ctv' => 'american',
        'bbc' => 'european', 'guardian' => 'european', 'independent' => 'european',
        'telegraph' => 'european', 'sky news' => 'european', 'reuters' => 'european',
        'deutsche welle' => 'european', 'france 24' => 'european', 'euronews' => 'european',
        'financial times' => 'european', 'economist' => 'european',
        'al jazeera' => 'asian', 'times of india' => 'asian', 'hindustan' => 'asian',
        'the hindu' => 'asian', 'ndtv' => 'asian', 'south china' => 'asian',
        'japan times' => 'asian', 'straits times' => 'asian', 'times of israel' => 'asian',
        'jerusalem post' => 'asian', 'haaretz' => 'asian', 'nikkei' => 'asian',
    ];

    /** Country-code TLD → region (last-resort fallback). */
    private const TLDS = [
        'us' => 'american', 'ca' => 'american',
        'uk' => 'european', 'ie' => 'european', 'de' => 'european', 'fr' => 'european',
        'es' => 'european', 'it' => 'european', 'nl' => 'european', 'se' => 'european',
        'no' => 'european', 'dk' => 'european', 'fi' => 'european', 'be' => 'european',
        'at' => 'european', 'ch' => 'european', 'pl' => 'european', 'pt' => 'european',
        'gr' => 'european', 'cz' => 'european', 'eu' => 'european',
        'in' => 'asian', 'jp' => 'asian', 'cn' => 'asian', 'hk' => 'asian', 'sg' => 'asian',
        'kr' => 'asian', 'tw' => 'asian', 'my' => 'asian', 'id' => 'asian', 'ph' => 'asian',
        'th' => 'asian', 'vn' => 'asian', 'il' => 'asian', 'qa' => 'asian', 'ae' => 'asian',
        'sa' => 'asian', 'pk' => 'asian', 'bd' => 'asian',
    ];

    public static function priority(?string $source, ?string $url = null): int
    {
        $host = self::host($url);

        if ($host !== '') {
            foreach (self::DOMAINS as $needle => $region) {
                if (str_contains($host, $needle)) {
                    return self::RANK[$region];
                }
            }
        }

        $name = mb_strtolower(trim((string) $source));
        if ($name !== '') {
            foreach (self::SOURCE_KEYWORDS as $needle => $region) {
                if (str_contains($name, $needle)) {
                    return self::RANK[$region];
                }
            }
        }

        if ($host !== '') {
            $tld = self::tld($host);
            if ($tld !== '' && isset(self::TLDS[$tld])) {
                return self::RANK[self::TLDS[$tld]];
            }
        }

        return self::RANK['other'];
    }

    /**
     * Order a collection of Article models for display: by region, then by the
     * stored position (newest-first) within each region. Stable and non-mutating.
     *
     * @param  Collection<int, \App\Models\Article>  $articles
     * @return Collection<int, \App\Models\Article>
     */
    public static function order(Collection $articles): Collection
    {
        return $articles
            ->sortBy(fn ($a) => self::priority($a->source, $a->url) * 1000 + (int) $a->position)
            ->values();
    }

    private static function host(?string $url): string
    {
        if (! $url) {
            return '';
        }

        $host = parse_url(trim($url), PHP_URL_HOST);

        return $host ? strtolower(preg_replace('/^www\./', '', $host)) : '';
    }

    private static function tld(string $host): string
    {
        $parts = explode('.', $host);

        return end($parts) ?: '';
    }
}
