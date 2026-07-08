<?php

namespace Tests\Feature;

use App\Contracts\ArticleProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class RefreshCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(ArticleProvider::class, new FakeArticleProvider());
    }

    public function test_due_flag_only_refreshes_users_whose_local_hour_matches(): void
    {
        // Freeze time at 11:00 UTC.
        Carbon::setTestNow(Carbon::create(2026, 6, 16, 11, 0, 0, 'UTC'));

        // Due: UTC user whose refresh hour is 11.
        $due = User::factory()->create(['timezone' => 'UTC', 'refresh_hour' => 11]);
        $dueTopic = $due->topics()->create(['name' => 'Due Topic', 'position' => 0]);

        // Not due: UTC user whose refresh hour is 10.
        $notDue = User::factory()->create(['timezone' => 'UTC', 'refresh_hour' => 10]);
        $notDueTopic = $notDue->topics()->create(['name' => 'Other Topic', 'position' => 0]);

        $this->artisan('newspaperflow:refresh', ['--due' => true])->assertSuccessful();

        $this->assertSame(12, $dueTopic->articles()->count());
        $this->assertSame(0, $notDueTopic->articles()->count());

        Carbon::setTestNow();
    }

    public function test_timezone_is_respected_for_due_calculation(): void
    {
        // 11:00 UTC == 06:00 in America/Chicago (CDT, UTC-5 in June).
        Carbon::setTestNow(Carbon::create(2026, 6, 16, 11, 0, 0, 'UTC'));

        $chicago = User::factory()->create(['timezone' => 'America/Chicago', 'refresh_hour' => 6]);
        $topic = $chicago->topics()->create(['name' => 'Chicago Topic', 'position' => 0]);

        $this->artisan('newspaperflow:refresh', ['--due' => true])->assertSuccessful();

        $this->assertSame(12, $topic->articles()->count());

        Carbon::setTestNow();
    }

    public function test_refresh_without_flags_refreshes_all_topics(): void
    {
        $user = User::factory()->create();
        $a = $user->topics()->create(['name' => 'A', 'position' => 0]);
        $b = $user->topics()->create(['name' => 'B', 'position' => 1]);

        $this->artisan('newspaperflow:refresh')->assertSuccessful();

        $this->assertSame(12, $a->articles()->count());
        $this->assertSame(12, $b->articles()->count());
    }
}
