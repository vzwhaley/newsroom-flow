<?php

namespace App\Services\Articles;

use App\Models\DiscoveredLocalSource;
use App\Models\Topic;

/**
 * Resolves the local-outlet domains for an area (precision layer 2).
 *
 * Resolution order:
 *   1. Curated exact metro   (config/localnews.php 'metros')
 *   2. AI-discovered cache    (discovered_local_sources — self-learning)
 *   3. Curated US statewide   (config 'us_states')
 *   4. Curated country-level  (config 'countries')
 *
 * Returns an empty list when nothing matches — the area still works on its
 * geocoded query alone (precision layer 1).
 */
class LocalSources
{
    /**
     * @return array<int, string>
     */
    public function forArea(Topic $area): array
    {
        // 1. Curated exact metro.
        if ($metro = $this->curatedMetro($area)) {
            return $metro;
        }

        // 2. AI-discovered cache for this exact location.
        if ($discovered = $this->discovered($area)) {
            return $discovered;
        }

        // 3–4. Curated statewide / country fallback.
        return $this->curatedFallback($area);
    }

    /**
     * The curated exact-metro match, if any (no discovery / fallback).
     *
     * @return array<int, string>
     */
    public function curatedMetro(Topic $area): array
    {
        $city    = strtolower(trim((string) $area->locality));
        $region  = strtolower(trim((string) $area->region));
        $country = strtolower(trim((string) $area->country_code));

        if ($city === '') {
            return [];
        }

        $metros = (array) config('localnews.metros', []);

        $usKey   = $region !== '' ? "{$city},{$region}" : null;
        $intlKey = $country !== '' ? "{$city},{$country}" : null;

        if ($usKey && isset($metros[$usKey])) {
            return array_values($metros[$usKey]);
        }
        if ($intlKey && isset($metros[$intlKey])) {
            return array_values($metros[$intlKey]);
        }

        return [];
    }

    /**
     * Whether a curated metro entry already covers this area (so discovery
     * can skip it).
     */
    public function hasCuratedMetro(Topic $area): bool
    {
        return $this->curatedMetro($area) !== [];
    }

    /**
     * AI-discovered domains cached for this area's location, if any.
     *
     * @return array<int, string>
     */
    private function discovered(Topic $area): array
    {
        $record = DiscoveredLocalSource::forArea($area);

        return $record && is_array($record->domains) ? array_values($record->domains) : [];
    }

    /**
     * Curated statewide (US) then country-level fallback.
     *
     * @return array<int, string>
     */
    private function curatedFallback(Topic $area): array
    {
        $region  = strtolower(trim((string) $area->region));
        $country = strtolower(trim((string) $area->country_code));

        // US statewide.
        if ($country === 'us' && $region !== '') {
            $states = (array) config('localnews.us_states', []);
            if (isset($states[$region])) {
                return array_values($states[$region]);
            }
        }

        // Country-level (international, or US with no state match).
        if ($country !== '' && $country !== 'us') {
            $countries = (array) config('localnews.countries', []);
            if (isset($countries[$country])) {
                return array_values($countries[$country]);
            }
        }

        return [];
    }

    /**
     * The lowercase ISO country code the news APIs should be scoped to, or
     * null (US areas pass 'us'; international pass their code).
     */
    public function countryCode(Topic $area): ?string
    {
        $code = strtolower(trim((string) $area->country_code));

        return $code !== '' ? $code : null;
    }

    /**
     * Normalized cache key for a location: "country|region|city" (lowercase).
     * Used by the discovered-source cache so a place is discovered once and
     * reused across all users.
     */
    public static function keyFor(?string $city, ?string $region, ?string $country): string
    {
        $c = strtolower(trim((string) $country));
        $r = strtolower(trim((string) $region));
        $t = strtolower(trim((string) $city));
        $t = preg_replace('/\s+/', ' ', $t) ?? $t;

        return "{$c}|{$r}|{$t}";
    }
}
