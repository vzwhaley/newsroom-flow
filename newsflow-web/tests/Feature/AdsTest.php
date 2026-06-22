<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdsTest extends TestCase
{
    use RefreshDatabase;

    private function configureAdsense(): void
    {
        config([
            'adsense.client' => 'ca-pub-1234567890123456',
            'adsense.slots'  => ['home_top' => '1111111111', 'dashboard_top' => '2222222222'],
        ]);
    }

    public function test_anonymous_visitor_is_ad_eligible(): void
    {
        $this->configureAdsense();

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('adsense.shows_ads', true)
            ->where('adsense.client', 'ca-pub-1234567890123456')
        );
    }

    public function test_free_user_is_ad_eligible_on_dashboard(): void
    {
        $this->configureAdsense();
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('adsense.shows_ads', true)
            ->where('adsense.client', 'ca-pub-1234567890123456')
        );
    }

    public function test_pro_user_receives_no_client_or_slots(): void
    {
        $this->configureAdsense();
        $pro = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);

        $this->actingAs($pro)->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('adsense.shows_ads', false)
            ->where('adsense.client', null)
            ->where('adsense.slots', [])
        );
    }

    public function test_ads_txt_serves_authorized_seller_line_when_configured(): void
    {
        config(['adsense.client' => 'ca-pub-1234567890123456']);

        $this->get('/ads.txt')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0', false);
    }

    public function test_ads_txt_is_empty_comment_when_unconfigured(): void
    {
        config(['adsense.client' => null]);

        $this->get('/ads.txt')->assertOk()->assertSee('No publisher ID configured', false);
    }

    public function test_app_ads_txt_uses_admob_publisher(): void
    {
        config(['admob.app_id.android' => 'ca-app-pub-9876543210987654~1234567890']);

        $this->get('/app-ads.txt')
            ->assertOk()
            ->assertSee('google.com, pub-9876543210987654, DIRECT, f08c47fec0942fa0', false);
    }

    public function test_api_config_serves_admob_units_for_free(): void
    {
        config([
            'admob.units'  => ['feed_tab' => 'ca-app-pub-111/222'],
            'admob.app_id' => ['android' => 'ca-app-pub-111~333', 'ios' => null],
        ]);
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        Sanctum::actingAs($user);

        $this->getJson('/api/config')->assertOk()
            ->assertJsonPath('data.ads.show', true)
            ->assertJsonPath('data.ads.units.feed_tab', 'ca-app-pub-111/222');
    }

    public function test_api_config_hides_admob_units_for_pro(): void
    {
        config(['admob.units' => ['feed_tab' => 'ca-app-pub-111/222']]);
        $pro = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);
        Sanctum::actingAs($pro);

        $this->getJson('/api/config')->assertOk()
            ->assertJsonPath('data.ads.show', false)
            ->assertJsonPath('data.ads.units', null);
    }
}
