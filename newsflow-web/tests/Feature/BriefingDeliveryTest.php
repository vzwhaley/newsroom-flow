<?php

namespace Tests\Feature;

use App\Mail\DailyDigest;
use App\Models\User;
use App\Services\Push\PushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakePushSender;
use Tests\TestCase;

/**
 * The daily briefing riding the existing delivery channels: the morning push
 * body (Pro) and the top of the digest email (Pro).
 */
class BriefingDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function fakeNotifier(): FakePushSender
    {
        $fake = new FakePushSender('ios');
        $this->app->instance(PushNotifier::class, new PushNotifier(['ios' => $fake, 'android' => $fake]));

        return $fake;
    }

    private function userWithArticle(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email_verified_at' => Carbon::now(),
            'push_enabled'      => true,
        ], $attributes));

        $topic = $user->topics()->create(['name' => 'Space', 'position' => 0, 'include_in_digest' => true]);
        $topic->articles()->create([
            'headline' => 'Rocket reaches orbit', 'description' => 'A big launch.',
            'url' => 'https://e.test/r', 'fingerprint' => 'r1', 'position' => 0, 'fetched_at' => Carbon::now(),
        ]);

        return $user;
    }

    public function test_pro_morning_push_body_is_the_briefing(): void
    {
        config()->set('newsflow.llm.enabled', false); // deterministic fallback text

        $fake = $this->fakeNotifier();
        $user = $this->userWithArticle(['lifetime_purchased_at' => Carbon::now()]);
        $user->deviceTokens()->create(['platform' => 'ios', 'token' => 'tok-1']);

        $this->artisan('newsflow:push --user='.$user->id)->assertSuccessful();

        $this->assertCount(1, $fake->sent);
        $message = $fake->sent[0]['message'];
        $this->assertSame('Your daily briefing', $message->title);
        $this->assertStringContainsString('Space', $message->body);
        $this->assertSame('briefing', $message->data['type']);
    }

    public function test_watchlist_hit_still_beats_the_briefing(): void
    {
        config()->set('newsflow.llm.enabled', false);

        $fake = $this->fakeNotifier();
        $user = $this->userWithArticle([
            'lifetime_purchased_at' => Carbon::now(),
            'watch_keywords'        => ['rocket'],
        ]);
        $user->deviceTokens()->create(['platform' => 'ios', 'token' => 'tok-1']);

        $this->artisan('newsflow:push --user='.$user->id)->assertSuccessful();

        $this->assertCount(1, $fake->sent);
        $this->assertSame('In your watchlist', $fake->sent[0]['message']->title);
    }

    public function test_free_morning_push_keeps_the_generic_summary(): void
    {
        $fake = $this->fakeNotifier();
        $user = $this->userWithArticle();
        $user->deviceTokens()->create(['platform' => 'ios', 'token' => 'tok-1']);

        $this->artisan('newsflow:push --user='.$user->id)->assertSuccessful();

        $this->assertCount(1, $fake->sent);
        $this->assertSame('Your NewsFlow is ready', $fake->sent[0]['message']->title);
    }

    public function test_pro_digest_email_opens_with_the_briefing(): void
    {
        config()->set('newsflow.llm.enabled', false);
        Mail::fake();

        $user = $this->userWithArticle([
            'lifetime_purchased_at' => Carbon::now(),
            'digest_enabled'        => true,
        ]);

        $this->artisan('newsflow:digest --user='.$user->id)->assertSuccessful();

        Mail::assertSent(DailyDigest::class, function (DailyDigest $mail) {
            return filled($mail->briefing)
                && str_contains($mail->briefing, 'Space')
                && str_contains($mail->render(), 'Your Daily Briefing');
        });
    }

    public function test_free_digest_email_has_no_briefing(): void
    {
        Mail::fake();

        $user = $this->userWithArticle(['digest_enabled' => true]);

        $this->artisan('newsflow:digest --user='.$user->id)->assertSuccessful();

        Mail::assertSent(DailyDigest::class, fn (DailyDigest $mail) => is_null($mail->briefing));
    }
}
