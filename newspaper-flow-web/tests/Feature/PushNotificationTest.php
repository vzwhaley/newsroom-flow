<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Push\PushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakePushSender;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeNotifier(): FakePushSender
    {
        $fake = new FakePushSender('ios');
        $this->app->instance(PushNotifier::class, new PushNotifier(['ios' => $fake, 'android' => $fake]));

        return $fake;
    }

    public function test_register_and_remove_a_device_token_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        Sanctum::actingAs($user);

        $this->postJson('/api/device-tokens', ['platform' => 'ios', 'token' => 'apns-abc'])
            ->assertOk()->assertJsonPath('registered', true);
        $this->assertDatabaseHas('device_tokens', ['user_id' => $user->id, 'token' => 'apns-abc', 'platform' => 'ios']);

        // Re-registering the same token is an upsert, not a duplicate.
        $this->postJson('/api/device-tokens', ['platform' => 'ios', 'token' => 'apns-abc'])->assertOk();
        $this->assertSame(1, DeviceToken::where('token', 'apns-abc')->count());

        $this->deleteJson('/api/device-tokens', ['token' => 'apns-abc'])->assertOk();
        $this->assertDatabaseMissing('device_tokens', ['token' => 'apns-abc']);
    }

    public function test_push_enabled_persists_via_preferences(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        Sanctum::actingAs($user);

        $this->putJson('/api/preferences', [
            'refresh_hour' => 6, 'timezone' => 'UTC', 'digest_enabled' => false, 'digest_new_only' => false,
            'push_enabled' => true,
        ])->assertOk()->assertJsonPath('user.push_enabled', true);

        $this->assertTrue($user->fresh()->push_enabled);
        $this->getJson('/api/me')->assertJsonPath('user.push_enabled', true);
    }

    public function test_daily_push_sends_generic_summary_and_sets_cutoff(): void
    {
        $fake = $this->fakeNotifier();

        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'push_enabled' => true]);
        $user->deviceTokens()->create(['platform' => 'ios', 'token' => 'tok-1']);
        $topic = $user->topics()->create(['name' => 'World', 'position' => 0]);
        $topic->articles()->create(['headline' => 'A', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0, 'fetched_at' => Carbon::now()]);
        $topic->articles()->create(['headline' => 'B', 'description' => 'x', 'url' => 'https://e.test/b', 'fingerprint' => 'b', 'position' => 1, 'fetched_at' => Carbon::now()]);

        $this->artisan('newspaperflow:push --user='.$user->id)->assertSuccessful();

        $this->assertCount(1, $fake->sent);
        $this->assertSame('Your NewsroomFlow is ready', $fake->sent[0]['message']->title);
        $this->assertSame('2 new stories across your topics.', $fake->sent[0]['message']->body);
        $this->assertNotNull($user->fresh()->push_sent_at);
    }

    public function test_daily_push_features_a_watchlist_hit_for_pro(): void
    {
        $fake = $this->fakeNotifier();

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now(),
            'push_enabled' => true, 'watch_keywords' => ['tesla'],
        ]);
        $user->deviceTokens()->create(['platform' => 'ios', 'token' => 'tok-1']);
        $topic = $user->topics()->create(['name' => 'Cars', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Tesla unveils new model', 'description' => 'x', 'url' => 'https://e.test/t', 'fingerprint' => 't', 'position' => 0, 'fetched_at' => Carbon::now()]);

        $this->artisan('newspaperflow:push --user='.$user->id)->assertSuccessful();

        $this->assertCount(1, $fake->sent);
        $this->assertSame('In your watchlist', $fake->sent[0]['message']->title);
        $this->assertSame('Tesla unveils new model', $fake->sent[0]['message']->body);
        $this->assertSame('watchlist', $fake->sent[0]['message']->data['type']);
    }

    public function test_daily_push_skips_users_with_nothing_new(): void
    {
        $fake = $this->fakeNotifier();

        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'push_enabled' => true, 'push_sent_at' => Carbon::now()]);
        $user->deviceTokens()->create(['platform' => 'ios', 'token' => 'tok-1']);
        $topic = $user->topics()->create(['name' => 'World', 'position' => 0]);
        // Article fetched before the last push → not "new".
        $topic->articles()->create(['headline' => 'Old', 'description' => 'x', 'url' => 'https://e.test/o', 'fingerprint' => 'o', 'position' => 0, 'fetched_at' => Carbon::now()->subDay()]);

        $this->artisan('newspaperflow:push --user='.$user->id)->assertSuccessful();

        $this->assertCount(0, $fake->sent);
    }

    public function test_daily_push_prunes_invalid_tokens(): void
    {
        $fake = $this->fakeNotifier();
        $fake->invalidTokens = ['dead-token'];

        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'push_enabled' => true]);
        $user->deviceTokens()->create(['platform' => 'ios', 'token' => 'dead-token']);
        $topic = $user->topics()->create(['name' => 'World', 'position' => 0]);
        $topic->articles()->create(['headline' => 'A', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0, 'fetched_at' => Carbon::now()]);

        $this->artisan('newspaperflow:push --user='.$user->id)->assertSuccessful();

        $this->assertCount(0, $fake->sent);
        $this->assertDatabaseMissing('device_tokens', ['token' => 'dead-token']);
    }
}
