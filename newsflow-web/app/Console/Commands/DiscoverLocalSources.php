<?php

namespace App\Console\Commands;

use App\Models\DiscoveredLocalSource;
use App\Models\Topic;
use App\Services\Articles\LocalSourceDiscovery;
use App\Services\Articles\LocalSources;
use App\Services\Articles\TopicRefresher;
use Illuminate\Console\Command;

/**
 * Backfill / refresh the self-learning local-outlet directory.
 *
 *   php artisan newsflow:discover-sources                 # every area missing local sources
 *   php artisan newsflow:discover-sources --area=42       # one area
 *   php artisan newsflow:discover-sources --city="Erwin" --state=TN   # ad-hoc, no area needed
 *   php artisan newsflow:discover-sources --reverify      # re-check learned records past their TTL
 */
class DiscoverLocalSources extends Command
{
    protected $signature = 'newsflow:discover-sources
        {--area= : Discover for a single area (topic) id}
        {--city= : Ad-hoc city (with --state / --country), no area needed}
        {--state= : Ad-hoc US state abbreviation}
        {--country=US : Ad-hoc country code}
        {--reverify : Re-verify learned records older than the TTL}
        {--force : Discover even for locations already curated/cached}';

    protected $description = 'Discover and cache local news outlets for area locations (AI + web search).';

    public function handle(LocalSourceDiscovery $discovery, LocalSources $sources, TopicRefresher $refresher): int
    {
        if (! $discovery->enabled()) {
            $this->warn('Discovery is disabled (set ANTHROPIC_API_KEY and NEWSFLOW_DISCOVERY=true).');

            return self::SUCCESS;
        }

        // Ad-hoc: discover for a location without needing an existing area.
        if ($city = $this->option('city')) {
            $probe = new Topic([
                'kind'         => 'area',
                'locality'     => $city,
                'region'       => $this->option('state'),
                'country_code' => strtoupper((string) $this->option('country')),
            ]);
            $this->discoverOne($discovery, $probe, $refresher, reRefresh: false);

            return self::SUCCESS;
        }

        $areas = $this->targetAreas($sources);

        if ($areas->isEmpty()) {
            $this->info('No areas need discovery right now.');

            return self::SUCCESS;
        }

        $this->info("Discovering local sources for {$areas->count()} area(s)...");

        foreach ($areas as $area) {
            $this->discoverOne($discovery, $area, $refresher, reRefresh: true);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Topic>
     */
    private function targetAreas(LocalSources $sources)
    {
        $query = Topic::query()->where('kind', 'area');

        if ($id = $this->option('area')) {
            return $query->whereKey($id)->get();
        }

        return $query->get()->filter(function (Topic $area) use ($sources) {
            if ($this->option('force')) {
                return ! $sources->hasCuratedMetro($area);
            }
            if ($sources->hasCuratedMetro($area)) {
                return false;
            }
            if ($this->option('reverify')) {
                return ! DiscoveredLocalSource::freshFor($area);
            }

            // Default: only areas with no discovered record yet.
            return DiscoveredLocalSource::forArea($area) === null;
        })->values();
    }

    private function discoverOne(LocalSourceDiscovery $discovery, Topic $area, TopicRefresher $refresher, bool $reRefresh): void
    {
        $label = trim(($area->locality ?? '').', '.($area->region ?: $area->country_code));
        $this->line("  • {$label} ...");

        $result = $discovery->discover($area);

        if (empty($result['domains'])) {
            $this->line('      no local outlets found');
            if ($area->exists) {
                DiscoveredLocalSource::remember($area, [], []);
            }

            return;
        }

        $this->line('      '.implode(', ', $result['domains']));

        if ($area->exists) {
            DiscoveredLocalSource::remember($area, $result['domains'], $result['outlets']);
            if ($reRefresh) {
                try {
                    $refresher->refresh($area->fresh());
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
    }
}
