<?php

namespace Tests\Feature;

use App\Models\ReadingDay;
use App\Models\SharedArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StreaksAndSharesTest extends TestCase
{
    use RefreshDatabase;

    private function userWithArticle(): array
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'World', 'position' => 0]);
        $article = $topic->articles()->create([
            'headline' => 'Big news happens', 'description' => 'Details inside.',
            'url' => 'https://e.test/big', 'source' => 'The Wire',
            'fingerprint' => 'big1', 'position' => 0, 'fetched_at' => Carbon::now(),
        ]);

        return [$user, $topic, $article];
    }

    /*
    |----------------------------------------------------------------------
    | Streaks
    |----------------------------------------------------------------------
    */

    public function test_marking_read_records_a_reading_day(): void
    {
        [$user, , $article] = $this->userWithArticle();

        $this->actingAs($user)->postJson(route('articles.read', $article))->assertOk();

        $this->assertDatabaseHas('reading_days', ['user_id' => $user->id, 'reads' => 1]);

        // A second read the same day increments the counter, not the rows.
        $article2 = $article->topic->articles()->create([
            'headline' => 'More news', 'description' => 'x', 'url' => 'https://e.test/2',
            'fingerprint' => 'big2', 'position' => 1, 'fetched_at' => Carbon::now(),
        ]);
        $this->actingAs($user)->postJson(route('articles.read', $article2))->assertOk();

        $this->assertSame(1, ReadingDay::where('user_id', $user->id)->count());
        $this->assertSame(2, (int) ReadingDay::where('user_id', $user->id)->first()->reads);
    }

    public function test_reopening_an_already_read_article_still_records_daily_activity(): void
    {
        [$user, , $article] = $this->userWithArticle();

        // First open marks it read and records the day.
        $this->actingAs($user)->postJson(route('articles.read', $article))->assertOk();
        // Re-opening the SAME (already-read) article still counts toward the day,
        // so the streak stays alive even when there's nothing brand-new to read.
        $this->actingAs($user)->postJson(route('articles.read', $article))->assertOk();

        $this->assertSame(1, ReadingDay::where('user_id', $user->id)->count());
        $this->assertSame(2, (int) ReadingDay::where('user_id', $user->id)->first()->reads);
    }

    public function test_streak_counts_consecutive_days_and_survives_an_unread_morning(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'timezone' => 'UTC']);

        $today = Carbon::now('UTC')->startOfDay();

        // 3-day streak ending yesterday (nothing yet today).
        foreach ([1, 2, 3] as $daysAgo) {
            ReadingDay::create(['user_id' => $user->id, 'date' => $today->copy()->subDays($daysAgo), 'reads' => 2]);
        }

        $stats = ReadingDay::statsFor($user);
        $this->assertSame(3, $stats['streak']);
        $this->assertFalse($stats['read_today']);
        $this->assertSame(6, $stats['total_reads']);

        // Reading today extends it to 4.
        ReadingDay::create(['user_id' => $user->id, 'date' => $today, 'reads' => 1]);
        $stats = ReadingDay::statsFor($user);
        $this->assertSame(4, $stats['streak']);
        $this->assertTrue($stats['read_today']);
    }

    public function test_a_gap_breaks_the_streak(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'timezone' => 'UTC']);
        $today = Carbon::now('UTC')->startOfDay();

        ReadingDay::create(['user_id' => $user->id, 'date' => $today, 'reads' => 1]);
        // Day before yesterday — the gap at "yesterday" ends the streak at 1.
        ReadingDay::create(['user_id' => $user->id, 'date' => $today->copy()->subDays(2), 'reads' => 1]);

        $this->assertSame(1, ReadingDay::statsFor($user)['streak']);
    }

    public function test_streak_appears_in_api_me_and_dashboard_props(): void
    {
        [$user, , $article] = $this->userWithArticle();

        Sanctum::actingAs($user);
        $this->postJson('/api/articles/'.$article->id.'/read')->assertOk();

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.reading.streak', 1)
            ->assertJsonPath('user.reading.read_today', true)
            ->assertJsonPath('user.reading.total_reads', 1);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')->has('reading.streak'));
    }

    /*
    |----------------------------------------------------------------------
    | Share cards
    |----------------------------------------------------------------------
    */

    public function test_share_mints_a_link_and_reuses_it(): void
    {
        [$user, , $article] = $this->userWithArticle();

        $first = $this->actingAs($user)->postJson(route('articles.share', $article))
            ->assertOk()
            ->assertJsonStructure(['code', 'url'])
            ->json();

        // Sharing again returns the same code, not a duplicate.
        $second = $this->actingAs($user)->postJson(route('articles.share', $article))->assertOk()->json();
        $this->assertSame($first['code'], $second['code']);
        $this->assertSame(1, SharedArticle::count());
    }

    public function test_cannot_share_someone_elses_article(): void
    {
        [, , $article] = $this->userWithArticle();
        $stranger = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($stranger)->postJson(route('articles.share', $article))->assertForbidden();
    }

    public function test_share_page_renders_og_tags_and_counts_clicks(): void
    {
        [$user, , $article] = $this->userWithArticle();

        $code = $this->actingAs($user)->postJson(route('articles.share', $article))->json('code');

        // Public — no auth. Uses a fresh unauthenticated request.
        $response = $this->get('/s/'.$code);
        $response->assertOk()
            ->assertSee('Big news happens')
            ->assertSee('Read the Full Story')
            ->assertSee('og:title', false)
            ->assertSee('noindex', false)
            ->assertSee('https://e.test/big', false);

        $this->assertSame(1, (int) SharedArticle::where('code', $code)->first()->clicks);

        $this->get('/s/'.$code)->assertOk();
        $this->assertSame(2, (int) SharedArticle::where('code', $code)->first()->clicks);
    }

    public function test_unknown_share_code_is_404(): void
    {
        $this->get('/s/nosuchcode1')->assertNotFound();
    }

    public function test_share_works_for_free_users_via_api(): void
    {
        [$user, , $article] = $this->userWithArticle();
        Sanctum::actingAs($user);

        $this->postJson('/api/articles/'.$article->id.'/share')
            ->assertOk()
            ->assertJsonStructure(['code', 'url']);
    }
}
