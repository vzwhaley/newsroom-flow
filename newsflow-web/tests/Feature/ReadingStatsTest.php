<?php

namespace Tests\Feature;

use App\Models\ReadingDay;
use App\Models\SharedStreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReadingStatsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithHistory(int $days = 3): User
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'timezone' => 'UTC']);
        $today = Carbon::now('UTC')->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            ReadingDay::create(['user_id' => $user->id, 'date' => $today->copy()->subDays($i), 'reads' => $i + 1]);
        }

        return $user;
    }

    public function test_stats_page_renders_with_heatmap_data(): void
    {
        $user = $this->userWithHistory(3);

        $this->actingAs($user)->get('/stats')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Stats')
                ->where('stats.streak', 3)
                ->where('stats.longest_streak', 3)
                ->where('stats.days_active', 3)
                ->where('stats.total_reads', 6)
                ->has('stats.heatmap')
            );
    }

    public function test_longest_streak_survives_gaps(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'timezone' => 'UTC']);
        $today = Carbon::now('UTC')->startOfDay();

        // A 5-day run last month, a 2-day run ending today.
        foreach ([40, 41, 42, 43, 44] as $daysAgo) {
            ReadingDay::create(['user_id' => $user->id, 'date' => $today->copy()->subDays($daysAgo), 'reads' => 1]);
        }
        foreach ([0, 1] as $daysAgo) {
            ReadingDay::create(['user_id' => $user->id, 'date' => $today->copy()->subDays($daysAgo), 'reads' => 1]);
        }

        $stats = ReadingDay::fullStatsFor($user);
        $this->assertSame(2, $stats['streak']);
        $this->assertSame(5, $stats['longest_streak']);
        $this->assertSame(7, $stats['days_active']);
    }

    public function test_api_stats_returns_the_same_shape(): void
    {
        $user = $this->userWithHistory(2);
        Sanctum::actingAs($user);

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('stats.streak', 2)
            ->assertJsonStructure(['stats' => ['streak', 'read_today', 'total_reads', 'longest_streak', 'days_active', 'heatmap', 'from', 'to']]);
    }

    public function test_streak_share_mints_and_reuses_a_card(): void
    {
        $user = $this->userWithHistory(4);

        $first = $this->actingAs($user)->postJson(route('stats.share'))
            ->assertOk()
            ->assertJsonStructure(['code', 'url'])
            ->json();

        // Same streak → same card, no duplicate.
        $second = $this->actingAs($user)->postJson(route('stats.share'))->assertOk()->json();
        $this->assertSame($first['code'], $second['code']);
        $this->assertSame(1, SharedStreak::count());
    }

    public function test_share_requires_an_active_streak(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)->postJson(route('stats.share'))->assertStatus(422);
    }

    public function test_public_streak_card_renders_og_tags_and_counts_clicks(): void
    {
        $user = $this->userWithHistory(7);
        $code = $this->actingAs($user)->postJson(route('stats.share'))->json('code');

        $this->get('/streak/'.$code)
            ->assertOk()
            ->assertSee('7-Day')
            ->assertSee('reading streak', false)
            ->assertSee('og:title', false)
            ->assertSee('noindex', false)
            ->assertSee('Start Your Own Streak');

        $this->assertSame(1, (int) SharedStreak::where('code', $code)->first()->clicks);
    }

    public function test_unknown_streak_code_is_404(): void
    {
        $this->get('/streak/nosuchcode1')->assertNotFound();
    }
}
