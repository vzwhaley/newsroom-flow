<?php

namespace App\Services\Articles;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Turns a user's location input (US: city/state/ZIP · international:
 * city/country) into the structured fields an area feed needs: a clean
 * display label, a search query tuned for news relevance, and normalized
 * locality/region/postal/country values.
 *
 * ZIP → city/state enrichment is best-effort via the keyless Zippopotam.us
 * geocoder; if it's unavailable the user's typed city/state is used as-is.
 */
class LocationQuery
{
    /**
     * @param  array{country_code?:string,city?:string,state?:string,zip?:string}  $input
     * @return array{label:string, query:string, locality:?string, region:?string, postal_code:?string, country_code:string}
     */
    public function resolve(array $input): array
    {
        $country = strtoupper(trim($input['country_code'] ?? 'US')) ?: 'US';
        $city    = $this->clean($input['city'] ?? null);
        $state   = $this->clean($input['state'] ?? null);
        $zip     = $this->clean($input['zip'] ?? null);

        // US: if a ZIP is supplied, use it to confirm/enrich the city+state.
        if ($country === 'US' && $zip) {
            $geo = $this->geocodeUsZip($zip);
            if ($geo) {
                $city  = $city ?: $geo['city'];
                $state = $state ?: $geo['state'];
            }
        }

        return $country === 'US'
            ? $this->buildUs($city, $state, $zip)
            : $this->buildInternational($city, $country);
    }

    /**
     * @return array{label:string, query:string, locality:?string, region:?string, postal_code:?string, country_code:string}
     */
    private function buildUs(?string $city, ?string $state, ?string $zip): array
    {
        $state = $state ? strtoupper($state) : null;

        // Label: "Cleveland, OH" (or the ZIP if that's all we have).
        $label = collect([$city, $state])->filter()->implode(', ');
        if ($label === '') {
            $label = $zip ? "ZIP {$zip}" : 'Local';
        }

        // Query: quote the city so the API treats it as a phrase, add the full
        // state name for disambiguation ("Springfield" is everywhere).
        $parts = [];
        if ($city) {
            $parts[] = '"'.$city.'"';
        }
        if ($state) {
            $parts[] = $this->usStateName($state) ?? $state;
        }
        $query = trim(implode(' ', $parts)) ?: ($zip ?? 'local news');

        return [
            'label'        => $label,
            'query'        => $query,
            'locality'     => $city,
            'region'       => $state,
            'postal_code'  => $zip,
            'country_code' => 'US',
        ];
    }

    /**
     * @return array{label:string, query:string, locality:?string, region:?string, postal_code:?string, country_code:string}
     */
    private function buildInternational(?string $city, string $country): array
    {
        $countryName = $this->countryName($country) ?? $country;
        $label = $city ? "{$city}, {$countryName}" : $countryName;

        $parts = [];
        if ($city) {
            $parts[] = '"'.$city.'"';
        }
        $parts[] = '"'.$countryName.'"';
        $query = implode(' ', $parts);

        return [
            'label'        => $label,
            'query'        => $query,
            'locality'     => $city,
            'region'       => null,
            'postal_code'  => null,
            'country_code' => $country,
        ];
    }

    /**
     * @return array{city:string, state:string}|null
     */
    private function geocodeUsZip(string $zip): ?array
    {
        if (! preg_match('/^\d{5}$/', $zip)) {
            return null;
        }

        try {
            $base = rtrim((string) config('newsflow.areas.zip_geocoder'), '/');
            $response = Http::timeout(6)->get("{$base}/us/{$zip}");

            if (! $response->ok()) {
                return null;
            }

            $place = $response->json('places.0');
            if (! is_array($place)) {
                return null;
            }

            $city  = trim((string) ($place['place name'] ?? ''));
            $state = trim((string) ($place['state abbreviation'] ?? ''));

            return $city !== '' && $state !== '' ? ['city' => $city, 'state' => $state] : null;
        } catch (\Throwable $e) {
            Log::info('ZIP geocode failed', ['zip' => $zip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value === '' ? null : Str::limit($value, 80, '');
    }

    private function usStateName(string $abbr): ?string
    {
        return self::US_STATES[strtoupper($abbr)] ?? null;
    }

    private function countryName(string $code): ?string
    {
        return self::COUNTRIES[strtoupper($code)] ?? null;
    }

    public const US_STATES = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
        'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
        'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
        'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
        'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
        'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
        'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
        'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
        'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
        'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
        'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    ];

    // Countries NewsFlow's news sources cover well in English. Extend as needed.
    public const COUNTRIES = [
        'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada',
        'AU' => 'Australia', 'IE' => 'Ireland', 'NZ' => 'New Zealand',
        'IN' => 'India', 'ZA' => 'South Africa', 'SG' => 'Singapore',
        'DE' => 'Germany', 'FR' => 'France', 'NL' => 'Netherlands',
        'ES' => 'Spain', 'IT' => 'Italy', 'MX' => 'Mexico', 'BR' => 'Brazil',
        'JP' => 'Japan', 'PH' => 'Philippines', 'NG' => 'Nigeria', 'KE' => 'Kenya',
    ];
}
