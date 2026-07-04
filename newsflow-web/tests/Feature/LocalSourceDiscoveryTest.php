<?php

namespace Tests\Feature;

use App\Contracts\ArticleProvider;
use App\Jobs\DiscoverAreaLocalSources;
use App\Models\DiscoveredLocalSource;
use App\Models\Topic;
use App\Models\User;
use App\Services\Articles\LocalSourceDiscovery;
use App\Services\Articles\LocalSources;
use App\Services\Articles\TopicRefresher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class LocalSourceDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(ArticleProvider::class, new FakeArticleProvider());

        // Enable discovery with a fake key + endpoint.
        config()->set('newsflow.discovery.enabled', true);
        config()->set('newsflow.llm.api_key', 'test-key');
        config()->set('newsflow.llm.endpoint', 'https://api.anthropic.com/v1/messages');
        config()->set('newsflow.discovery.validate_liveness', false);
    }

    private function area(string $city, string $region = 'TN', string $country = 'US'): Topic
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);

        return $user->areas()->create([
            'kind' => 'area', 'name' => "$city, $region", 'query' => "\"$city\" $region",
            'locality' => $city, 'region' => $region, 'country_code' => $country, 'position' => 0,
        ]);
    }

    private function fakeAnthropic(array $domains, array $outlets = []): void
    {
        $json = json_encode(['domains' => $domains, 'outlets' => $outlets]);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'server_tool_use', 'name' => 'web_search'],
                    ['type' => 'text', 'text' => "Here are the outlets:\n{$json}"],
                ],
            ]),
        ]);
    }

    public function test_discovery_parses_filters_and_caches(): void
    {
        // Returns two genuine local outlets + one national (must be filtered)
        // + a messy protocol/www form (must be normalized).
        $this->fakeAnthropic(
            ['https://www.erwinrecord.net/', 'wjhl.com', 'nytimes.com'],
            ['erwinrecord.net' => 'The Erwin Record', 'wjhl.com' => 'WJHL News Channel 11'],
        );

        $area = $this->area('Erwin'); // not in curated directory

        $discovery = app(LocalSourceDiscovery::class);
        $result = $discovery->discover($area);

        $this->assertSame(['erwinrecord.net', 'wjhl.com'], $result['domains']);
        $this->assertArrayNotHasKey('nytimes.com', array_flip($result['domains'])); // national filtered
        $this->assertSame('The Erwin Record', $result['outlets']['erwinrecord.net']);
    }

    public function test_job_caches_and_makes_discovery_available_to_resolution(): void
    {
        $this->fakeAnthropic(['erwinrecord.net', 'wjhl.com', 'wcyb.com']);
        $area = $this->area('Erwin');

        (new DiscoverAreaLocalSources($area->id))->handle(
            app(LocalSourceDiscovery::class), app(LocalSources::class), app(TopicRefresher::class),
        );

        $this->assertDatabaseHas('discovered_local_sources', ['location_key' => 'us|tn|erwin']);

        // Now resolution returns the discovered outlets for that town.
        $resolved = app(LocalSources::class)->forArea($area->fresh());
        $this->assertContains('erwinrecord.net', $resolved);
        $this->assertContains('wjhl.com', $resolved);
    }

    public function test_curated_metro_wins_over_discovered(): void
    {
        // Seed a (bogus) discovered record for Cleveland, which IS curated.
        DiscoveredLocalSource::create([
            'location_key' => 'us|oh|cleveland', 'city' => 'Cleveland', 'region' => 'OH',
            'country_code' => 'US', 'domains' => ['bogus-discovered.com'], 'verified_at' => Carbon::now(),
        ]);

        $area = $this->area('Cleveland', 'OH');
        $resolved = app(LocalSources::class)->forArea($area);

        $this->assertContains('cleveland.com', $resolved);     // curated wins
        $this->assertNotContains('bogus-discovered.com', $resolved);
    }

    public function test_resolution_falls_back_to_statewide_without_discovery(): void
    {
        $area = $this->area('Erwin'); // uncovered TN town, no discovered record yet
        $resolved = app(LocalSources::class)->forArea($area);

        // Falls back to curated Tennessee statewide outlets.
        $this->assertContains('tennessean.com', $resolved);
    }

    public function test_job_skips_curated_locations_without_calling_the_api(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => '{"domains":["x.com"]}']]])]);

        $area = $this->area('Cleveland', 'OH'); // curated → discovery should skip

        (new DiscoverAreaLocalSources($area->id))->handle(
            app(LocalSourceDiscovery::class), app(LocalSources::class), app(TopicRefresher::class),
        );

        $this->assertDatabaseMissing('discovered_local_sources', ['location_key' => 'us|oh|cleveland']);
        Http::assertNothingSent();
    }

    public function test_discovery_disabled_is_a_graceful_noop(): void
    {
        config()->set('newsflow.discovery.enabled', false);
        Http::fake();

        $area = $this->area('Erwin');
        $result = app(LocalSourceDiscovery::class)->discover($area);

        $this->assertSame([], $result['domains']);
        Http::assertNothingSent();
    }

    public function test_liveness_validation_drops_dead_domains(): void
    {
        config()->set('newsflow.discovery.validate_liveness', true);

        $this->fakeAnthropic(['alive-local.com', 'dead-local.com']);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => '{"domains":["alive-local.com","dead-local.com"]}']],
            ]),
            'alive-local.com/*' => Http::response('<html>ok</html>', 200),
            'dead-local.com/*'  => Http::response('nope', 500),
        ]);

        $result = app(LocalSourceDiscovery::class)->discover($this->area('Somewhere'));

        $this->assertContains('alive-local.com', $result['domains']);
        $this->assertNotContains('dead-local.com', $result['domains']);
    }

    public function test_area_creation_dispatches_the_discovery_job(): void
    {
        \Illuminate\Support\Facades\Bus::fake();

        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $this->actingAs($user)->post(route('areas.store'), [
            'country_code' => 'US', 'city' => 'Erwin', 'state' => 'TN',
        ])->assertRedirect();

        \Illuminate\Support\Facades\Bus::assertDispatched(DiscoverAreaLocalSources::class);
    }

    public function test_scheduled_sweep_queues_only_areas_that_need_discovery(): void
    {
        \Illuminate\Support\Facades\Bus::fake();

        $erwin = $this->area('Erwin', 'TN');           // uncovered, no record → queue
        $this->area('Cleveland', 'OH');                // curated → skip
        $norton = $this->area('Norton', 'TN');         // uncovered but freshly discovered → skip
        DiscoveredLocalSource::remember($norton, ['some-local.com']);

        $this->artisan('newsflow:discover-sources --reverify --queue')->assertSuccessful();

        \Illuminate\Support\Facades\Bus::assertDispatched(
            DiscoverAreaLocalSources::class,
            fn ($job) => $job->areaId === $erwin->id,
        );
        \Illuminate\Support\Facades\Bus::assertDispatchedTimes(DiscoverAreaLocalSources::class, 1);
    }

    public function test_scheduled_sweep_respects_the_limit(): void
    {
        \Illuminate\Support\Facades\Bus::fake();

        foreach (['Erwin', 'Unicoi', 'Elizabethton'] as $town) {
            $this->area($town, 'TN'); // three uncovered TN towns
        }

        $this->artisan('newsflow:discover-sources --queue --limit=2')->assertSuccessful();

        \Illuminate\Support\Facades\Bus::assertDispatchedTimes(DiscoverAreaLocalSources::class, 2);
    }
}
