<?php

namespace App\Models;

use App\Services\Articles\LocalSources;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A cached, AI-discovered set of local-outlet domains for one location.
 * Keyed by a normalized location string and shared across all users.
 */
class DiscoveredLocalSource extends Model
{
    protected $fillable = [
        'location_key', 'city', 'region', 'country_code',
        'domains', 'outlets', 'source', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'domains'     => 'array',
            'outlets'     => 'array',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * The cached record for an area's location, if one exists.
     */
    public static function forArea(Topic $area): ?self
    {
        return static::where('location_key', LocalSources::keyFor(
            $area->locality, $area->region, $area->country_code
        ))->first();
    }

    /**
     * Do we already have a still-fresh record for this area's location?
     * Outlets rebrand/merge over time, so records re-verify after a TTL.
     */
    public static function freshFor(Topic $area): bool
    {
        $record = static::forArea($area);

        if (! $record) {
            return false;
        }

        $ttlDays = (int) config('newspaperflow.discovery.reverify_days', 120);

        return $record->verified_at !== null
            && $record->verified_at->gt(now()->subDays($ttlDays));
    }

    /**
     * Upsert the discovered domains for an area's location.
     *
     * @param  array<int, string>  $domains
     * @param  array<string, string>  $outlets  domain => outlet name
     */
    public static function remember(Topic $area, array $domains, array $outlets = []): self
    {
        return static::updateOrCreate(
            ['location_key' => LocalSources::keyFor($area->locality, $area->region, $area->country_code)],
            [
                'city'         => $area->locality,
                'region'       => $area->region,
                'country_code' => $area->country_code,
                'domains'      => array_values($domains),
                'outlets'      => $outlets ?: null,
                'source'       => 'ai',
                'verified_at'  => Carbon::now(),
            ],
        );
    }
}
