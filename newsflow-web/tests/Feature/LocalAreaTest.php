<?php

namespace Tests\Feature;

use App\Contracts\ArticleProvider;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class LocalAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Deterministic feed population; no real network on area create/refresh.
        $this->app->instance(ArticleProvider::class, new FakeArticleProvider());
    }

    private function free(): User
    {
        return User::factory()->create(['email_verified_at' => Carbon::now()]);
    }

    private function pro(): User
    {
        return User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);
    }

    /*
    |----------------------------------------------------------------------
    | Creation, geocoding, query building
    |----------------------------------------------------------------------
    */

    public function test_us_area_builds_a_disambiguated_query_and_label(): void
    {
        $user = $this->free();

        $this->actingAs($user)->post(route('areas.store'), [
            'country_code' => 'US', 'city' => 'Austin', 'state' => 'TX',
        ])->assertRedirect();

        $area = $user->areas()->first();
        $this->assertNotNull($area);
        $this->assertSame('area', $area->kind);
        $this->assertSame('Austin, TX', $area->name);
        $this->assertStringContainsString('"Austin"', $area->query);
        $this->assertStringContainsString('Texas', $area->query); // full state name for disambiguation
        $this->assertSame('US', $area->country_code);
        $this->assertGreaterThan(0, $area->articles()->count()); // feed populated on create
    }

    public function test_international_area_uses_city_and_country(): void
    {
        $user = $this->pro();

        $this->actingAs($user)->post(route('areas.store'), [
            'country_code' => 'GB', 'city' => 'Manchester',
        ])->assertRedirect();

        $area = $user->areas()->first();
        $this->assertSame('Manchester, United Kingdom', $area->name);
        $this->assertStringContainsString('"Manchester"', $area->query);
        $this->assertStringContainsString('United Kingdom', $area->query);
        $this->assertSame('GB', $area->country_code);
    }

    public function test_zip_is_geocoded_to_city_and_state_when_city_missing(): void
    {
        Http::fake([
            'api.zippopotam.us/*' => Http::response([
                'places' => [[
                    'place name' => 'Cleveland',
                    'state abbreviation' => 'OH',
                ]],
            ]),
        ]);

        $user = $this->free();

        $this->actingAs($user)->post(route('areas.store'), [
            'country_code' => 'US', 'city' => 'Cleveland', 'state' => 'OH', 'zip' => '44113',
        ])->assertRedirect();

        $area = $user->areas()->first();
        $this->assertSame('44113', $area->postal_code);
        $this->assertSame('Cleveland', $area->locality);
        $this->assertSame('OH', $area->region);
    }

    public function test_us_area_requires_a_state(): void
    {
        $user = $this->free();

        $this->actingAs($user)->post(route('areas.store'), [
            'country_code' => 'US', 'city' => 'Austin',
        ])->assertSessionHasErrors('state');

        $this->assertSame(0, $user->areas()->count());
    }

    public function test_bad_zip_is_rejected(): void
    {
        $user = $this->free();

        $this->actingAs($user)->post(route('areas.store'), [
            'country_code' => 'US', 'city' => 'Austin', 'state' => 'TX', 'zip' => 'abcde',
        ])->assertSessionHasErrors('zip');
    }

    /*
    |----------------------------------------------------------------------
    | Free limit + 24h typo-grace lock
    |----------------------------------------------------------------------
    */

    public function test_free_user_gets_exactly_one_area(): void
    {
        $user = $this->free();

        $this->actingAs($user)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Austin', 'state' => 'TX'])->assertRedirect();
        $this->actingAs($user)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Dallas', 'state' => 'TX'])
            ->assertSessionHas('error');

        $this->assertSame(1, $user->areas()->count());
    }

    public function test_free_user_can_edit_within_the_grace_window(): void
    {
        $user = $this->free();
        $this->actingAs($user)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Austin', 'state' => 'TX']);
        $area = $user->areas()->first();

        // Fresh area → editable.
        $this->actingAs($user)->patch(route('areas.update', $area), ['country_code' => 'US', 'city' => 'Dallas', 'state' => 'TX'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Dallas, TX', $area->fresh()->name);
    }

    public function test_free_user_area_locks_after_the_grace_window(): void
    {
        $user = $this->free();
        $this->actingAs($user)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Austin', 'state' => 'TX']);
        $area = $user->areas()->first();

        // Age the area past the 24h grace window.
        $area->forceFill(['created_at' => Carbon::now()->subDays(2)])->save();

        $this->actingAs($user)->patch(route('areas.update', $area->fresh()), ['country_code' => 'US', 'city' => 'Dallas', 'state' => 'TX'])
            ->assertSessionHas('error');
        $this->actingAs($user)->delete(route('areas.destroy', $area->fresh()))
            ->assertSessionHas('error');

        $this->assertSame('Austin, TX', $area->fresh()->name);
        $this->assertSame(1, $user->areas()->count());
    }

    public function test_pro_user_can_add_edit_and_delete_freely(): void
    {
        $user = $this->pro();

        $this->actingAs($user)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Austin', 'state' => 'TX']);
        $this->actingAs($user)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Dallas', 'state' => 'TX']);
        $this->assertSame(2, $user->areas()->count());

        // Edit + delete even on an old area (Pro is never locked).
        $area = $user->areas()->first();
        $area->forceFill(['created_at' => Carbon::now()->subYear()])->save();

        $this->actingAs($user)->patch(route('areas.update', $area->fresh()), ['country_code' => 'GB', 'city' => 'London'])
            ->assertSessionHas('success');
        $this->actingAs($user)->delete(route('areas.destroy', $area->fresh()))->assertSessionHas('success');
        $this->assertSame(1, $user->areas()->count());
    }

    /*
    |----------------------------------------------------------------------
    | Separation from topics + authorization
    |----------------------------------------------------------------------
    */

    public function test_areas_do_not_count_against_the_topic_limit(): void
    {
        $user = $this->free(); // 2-topic limit

        $this->actingAs($user)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Austin', 'state' => 'TX']);

        // Area exists but topic slots are untouched.
        $this->assertSame(1, $user->areas()->count());
        $this->assertSame(0, $user->topics()->count());
        $this->assertTrue($user->fresh()->canAddTopic());

        // Can still add the full complement of topics.
        $this->actingAs($user)->post(route('topics.store'), ['name' => 'Formula 1'])->assertRedirect();
        $this->actingAs($user)->post(route('topics.store'), ['name' => 'Markets'])->assertRedirect();
        $this->assertSame(2, $user->fresh()->topics()->count());
        $this->assertSame(1, $user->fresh()->areas()->count());
    }

    public function test_cannot_touch_another_users_area(): void
    {
        $owner = $this->pro();
        $this->actingAs($owner)->post(route('areas.store'), ['country_code' => 'US', 'city' => 'Austin', 'state' => 'TX']);
        $area = $owner->areas()->first();

        $stranger = $this->pro();
        $this->actingAs($stranger)->patch(route('areas.update', $area), ['country_code' => 'US', 'city' => 'Dallas', 'state' => 'TX'])->assertForbidden();
        $this->actingAs($stranger)->delete(route('areas.destroy', $area))->assertForbidden();
    }

    /*
    |----------------------------------------------------------------------
    | API parity
    |----------------------------------------------------------------------
    */

    public function test_api_create_returns_area_and_me_reports_limits(): void
    {
        $user = $this->pro();
        Sanctum::actingAs($user);

        $this->postJson('/api/areas', ['country_code' => 'US', 'city' => 'Seattle', 'state' => 'WA'])
            ->assertCreated()
            ->assertJsonPath('area.name', 'Seattle, WA')
            ->assertJsonPath('area.locked', false)
            ->assertJsonStructure(['area' => ['id', 'name', 'articles']]);

        $this->getJson('/api/me')
            ->assertJsonPath('user.area_limit', null) // Pro = unlimited
            ->assertJsonPath('user.area_count', 1);

        $this->getJson('/api/feed')->assertOk()->assertJsonStructure(['areas']);
    }

    public function test_api_free_me_reports_area_limit_of_one(): void
    {
        $user = $this->free();
        Sanctum::actingAs($user);

        $this->getJson('/api/me')->assertJsonPath('user.area_limit', 1)->assertJsonPath('user.area_count', 0);
    }

    /*
    |----------------------------------------------------------------------
    | Location-aware refresh (biasing plumbing)
    |----------------------------------------------------------------------
    */

    public function test_area_refresh_scopes_the_news_api_to_country_and_local_domains(): void
    {
        // Configure a real source so the hybrid provider makes a scoped call.
        config()->set('newsflow.sources.newsdata.key', 'test-key');
        config()->set('newsflow.sources.newsdata.endpoint', 'https://newsdata.io/api/1/news');

        Http::fake([
            'newsdata.io/*' => Http::response(['results' => []]),
            '*'             => Http::response([], 200),
        ]);

        // Use the real hybrid provider for this test.
        $this->app->forgetInstance(ArticleProvider::class);

        $user = $this->pro();
        $area = $user->areas()->create([
            'kind' => 'area', 'name' => 'Cleveland, OH', 'query' => '"Cleveland" Ohio',
            'locality' => 'Cleveland', 'region' => 'OH', 'country_code' => 'US', 'position' => 0,
        ]);

        app(\App\Services\Articles\TopicRefresher::class)->refresh($area);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'newsdata.io')
                && $request['country'] === 'us'
                && str_contains((string) $request['domain'], 'cleveland'); // cleveland.com → 'cleveland'
        });
    }

    /**
     * Northeast Tennessee (Tri-Cities + Greene County) + Knoxville resolve to
     * genuinely local outlets, not the distant statewide fallback.
     */
    public function test_northeast_tennessee_towns_resolve_to_local_outlets(): void
    {
        $sources = new \App\Services\Articles\LocalSources();
        $area = function (string $city, string $region = 'TN') {
            $t = new Topic();
            $t->kind = 'area';
            $t->locality = $city;
            $t->region = $region;
            $t->country_code = 'US';

            return $t;
        };

        // Greene County towns → The Greeneville Sun.
        $this->assertContains('greenevillesun.com', $sources->forArea($area('Afton')));
        $this->assertContains('greenevillesun.com', $sources->forArea($area('Chuckey')));
        $this->assertContains('greenevillesun.com', $sources->forArea($area('Greeneville')));
        // Washington County towns → Johnson City Press + WETS.
        $this->assertContains('johnsoncitypress.com', $sources->forArea($area('Jonesborough')));
        $this->assertContains('johnsoncitypress.com', $sources->forArea($area('Telford')));
        $this->assertContains('wets.org', $sources->forArea($area('Johnson City')));
        // Sullivan County → Kingsport Times-News / Bristol Herald Courier.
        $this->assertContains('timesnews.net', $sources->forArea($area('Kingsport')));
        $this->assertContains('heraldcourier.com', $sources->forArea($area('Bristol')));
        // Every NE-TN town shares the WJHL regional TV anchor.
        foreach (['Afton', 'Chuckey', 'Limestone', 'Telford', 'Jonesborough', 'Kingsport'] as $town) {
            $this->assertContains('wjhl.com', $sources->forArea($area($town)), "$town should include WJHL");
        }
        // Knoxville + Nashville.
        $this->assertContains('knoxnews.com', $sources->forArea($area('Knoxville')));
        $this->assertContains('newschannel5.com', $sources->forArea($area('Nashville')));
    }
}
