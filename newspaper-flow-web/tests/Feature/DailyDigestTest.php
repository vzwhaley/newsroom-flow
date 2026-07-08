<?php

namespace Tests\Feature;

use App\Mail\DailyDigest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DailyDigestTest extends TestCase
{
    use RefreshDatabase;

    private function userWithFeed(array $attrs = []): User
    {
        $user = User::factory()->create($attrs);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $topic->articles()->create([
            'headline'    => 'Big story today',
            'description' => 'Something happened.',
            'url'         => 'https://example.test/story',
            'fingerprint' => 'fp1',
            'position'    => 0,
        ]);

        return $user;
    }

    public function test_excluded_topics_are_left_out_of_the_digest(): void
    {
        Mail::fake();
        $user = User::factory()->create(['digest_enabled' => true]);

        $inc = $user->topics()->create(['name' => 'World News', 'position' => 0, 'include_in_digest' => true]);
        $inc->articles()->create(['headline' => 'Kept', 'description' => 'x', 'url' => 'https://e.test/1', 'fingerprint' => '1', 'position' => 0]);

        $exc = $user->topics()->create(['name' => 'Sports', 'position' => 1, 'include_in_digest' => false]);
        $exc->articles()->create(['headline' => 'Dropped', 'description' => 'x', 'url' => 'https://e.test/2', 'fingerprint' => '2', 'position' => 0]);

        $this->artisan('newspaperflow:digest', ['--user' => $user->id])->assertSuccessful();

        Mail::assertSent(DailyDigest::class, function ($mail) {
            $names = collect($mail->topics)->pluck('name')->all();
            return in_array('World News', $names) && ! in_array('Sports', $names);
        });
    }

    public function test_new_only_digest_includes_only_fresh_articles(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'digest_enabled'  => true,
            'digest_new_only' => true,
            'digest_sent_at'  => Carbon::create(2026, 6, 15, 6, 0, 0),
        ]);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Old', 'description' => 'x', 'url' => 'https://e.test/old', 'fingerprint' => 'old', 'position' => 1, 'fetched_at' => Carbon::create(2026, 6, 14, 6, 0, 0)]);
        $topic->articles()->create(['headline' => 'Fresh', 'description' => 'x', 'url' => 'https://e.test/new', 'fingerprint' => 'new', 'position' => 0, 'fetched_at' => Carbon::create(2026, 6, 16, 6, 0, 0)]);

        $this->artisan('newspaperflow:digest', ['--user' => $user->id])->assertSuccessful();

        Mail::assertSent(DailyDigest::class, function ($mail) {
            $headlines = collect($mail->topics)->flatMap(fn ($t) => collect($t['articles'])->pluck('headline'))->all();
            return in_array('Fresh', $headlines) && ! in_array('Old', $headlines);
        });
    }

    public function test_new_only_digest_skips_user_when_nothing_new(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'digest_enabled'  => true,
            'digest_new_only' => true,
            'digest_sent_at'  => Carbon::create(2026, 6, 16, 6, 0, 0),
        ]);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Old', 'description' => 'x', 'url' => 'https://e.test/old', 'fingerprint' => 'old', 'position' => 0, 'fetched_at' => Carbon::create(2026, 6, 14, 6, 0, 0)]);

        $this->artisan('newspaperflow:digest', ['--user' => $user->id])->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_digest_mailable_renders_without_errors(): void
    {
        $user = $this->userWithFeed(['digest_enabled' => true]);
        $topic = $user->topics()->first();

        $mailable = new DailyDigest($user, [
            ['name' => $topic->name, 'articles' => $topic->articles],
        ]);

        $html = $mailable->render();

        $this->assertStringContainsString('Big story today', $html);
        $this->assertStringContainsString('World News', $html);
        $this->assertStringContainsString('Open my NewsroomFlow', $html);
    }

    public function test_digest_is_sent_to_opted_in_user(): void
    {
        Mail::fake();
        $user = $this->userWithFeed(['digest_enabled' => true]);

        $this->artisan('newspaperflow:digest', ['--user' => $user->id])->assertSuccessful();

        Mail::assertSent(DailyDigest::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertNotNull($user->fresh()->digest_sent_at);
    }

    public function test_digest_skips_users_who_opted_out(): void
    {
        Mail::fake();
        $user = $this->userWithFeed(['digest_enabled' => false]);

        $this->artisan('newspaperflow:digest')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_due_digest_only_sends_to_users_due_this_hour(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::create(2026, 6, 16, 13, 0, 0, 'UTC'));

        $due = $this->userWithFeed(['digest_enabled' => true, 'timezone' => 'UTC', 'refresh_hour' => 13]);
        $notDue = $this->userWithFeed(['digest_enabled' => true, 'timezone' => 'UTC', 'refresh_hour' => 8]);

        $this->artisan('newspaperflow:digest', ['--due' => true])->assertSuccessful();

        Mail::assertSent(DailyDigest::class, fn ($mail) => $mail->hasTo($due->email));
        Mail::assertNotSent(DailyDigest::class, fn ($mail) => $mail->hasTo($notDue->email));

        Carbon::setTestNow();
    }
}
