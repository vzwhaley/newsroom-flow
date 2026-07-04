<?php

namespace App\Jobs;

use App\Models\DiscoveredLocalSource;
use App\Models\Topic;
use App\Services\Articles\LocalSourceDiscovery;
use App\Services\Articles\LocalSources;
use App\Services\Articles\TopicRefresher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Discovers and caches the local outlets for a freshly-created area whose
 * location isn't in the curated directory, then re-refreshes the area so its
 * feed immediately benefits. One-time per location (cached globally); a no-op
 * for locations that are already curated or already discovered-and-fresh.
 */
class DiscoverAreaLocalSources implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public int $areaId)
    {
    }

    public function handle(
        LocalSourceDiscovery $discovery,
        LocalSources $sources,
        TopicRefresher $refresher,
    ): void {
        if (! $discovery->enabled()) {
            return;
        }

        $area = Topic::find($this->areaId);
        if (! $area || ! $area->isArea()) {
            return;
        }

        // Skip when the curated directory already covers this exact place,
        // or when we already have a fresh discovered record for it.
        if ($sources->hasCuratedMetro($area) || DiscoveredLocalSource::freshFor($area)) {
            return;
        }

        $result = $discovery->discover($area);

        // Cache the outcome regardless (records "we looked"); an empty result
        // still stamps verified_at so we don't re-run until the TTL lapses.
        DiscoveredLocalSource::remember($area, $result['domains'], $result['outlets']);

        // Only re-refresh when discovery actually found local outlets to bias
        // toward — otherwise the existing fallback feed is already correct.
        if (! empty($result['domains'])) {
            try {
                $refresher->refresh($area->fresh());
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
