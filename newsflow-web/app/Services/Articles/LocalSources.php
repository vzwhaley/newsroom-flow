<?php

namespace App\Services\Articles;

use App\Models\Topic;

/**
 * Resolves the curated local-outlet domains for an area (precision layer 2),
 * from config/localnews.php. Resolution order: exact metro → US state →
 * country. Returns an empty list when nothing matches — the area still works
 * on its geocoded query alone.
 */
class LocalSources
{
    /**
     * @return array<int, string>
     */
    public function forArea(Topic $area): array
    {
        $city    = strtolower(trim((string) $area->locality));
        $region  = strtolower(trim((string) $area->region));       // US state abbr
        $country = strtolower(trim((string) $area->country_code)); // ISO alpha-2

        $metros = (array) config('localnews.metros', []);

        // Exact metro: "city,st" for US, "city,cc" for international.
        if ($city !== '') {
            $usKey   = $region !== '' ? "{$city},{$region}" : null;
            $intlKey = $country !== '' ? "{$city},{$country}" : null;

            if ($usKey && isset($metros[$usKey])) {
                return array_values($metros[$usKey]);
            }
            if ($intlKey && isset($metros[$intlKey])) {
                return array_values($metros[$intlKey]);
            }
        }

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
}
