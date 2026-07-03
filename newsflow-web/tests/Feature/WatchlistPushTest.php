<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Articles\TopicRefresher;
use App\Services\Push\PushNotifier;
use App\Services\Push\WatchlistPusher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\FakeArticleProvider;
use Tests\Support\FakePushSender;
use Tests\TestCase;

class WatchlistPushTest extends TestCase
{
    use RefreshDatabase;

    private FakePushSender $fakeSender;

    private function refresher(FakeArticleProvider $provider): TopicRefresher
    {
        $this->fakeSender = new FakePushSender('android');
        $notifier = new PushNotifier(['android' => $this->fakeSender, 'ios' => $this->fakeSender]);

        return new TopicRefresher($provider, new WatchlistPusher($notifier));
    }

    private function proWatcher(array $keywords = ['tesla']): User
    {
        $user = User::factory()->create([
            'email_verified_at'      => Carbon::now(),
            'lifetime_purchased_at'  => Carbon::now(),
            'push_enabled'           => true,
            'watchlist_push_enabled' => true,
            'watch_keywords'         => $keywords,
        ]);
        $user->deviceTokens()->create(['platform' => 'android', 'token' => 'fcm-1']);

        return $user;
    }

    public function test_refresh_pushes_watchlist_hits_immediately(): void
    {
        $user = $this->proWatcher(['tesla']);
        $topic = $user->topics()->create(['name' => 'Cars', 'position' => 0]);

        $provider = (new FakeArticleProvider())->withFixed([
            ['Tesla announces new battery', 'https://e.test/t1'],
            ['Toyota ships hybrid', 'https://e.test/t2'],
        ]);

        $this->refresher($provider)->refresh($topic);

        $this->assertCount(1, $this->fakeSender->sent);
        $message = $this->fakeSender->sent[0]['message'];
        $this->assertSame('Watchlist: tesla', $message->title);
        $this->assertSame('Tesla announces new battery', $message->body);
        $this->assertSame('watchlist', $message->data['type']);
        $this->assertSame('Cars', $message->data['topic']);
    }

    public function test_no_push_when_toggle_is_off(): void
    {
        $user = $this->proWatcher(['tesla']);
        $user->forceFill(['watchlist_push_enabled' => false])->save();
        $topic = $user->topics()->create(['name' => 'Cars', 'position' => 0]);

        $provider = (new FakeArticleProvider())->withFixed([
            ['Tesla announces new battery', 'https://e.test/t1'],
        ]);

        $this->refresher($provider)->refresh($topic);

        $this->assertCount(0, $this->fakeSender->sent);
    }

    public function test_no_push_for_free_users(): void
    {
        $user = User::factory()->create([
            'email_verified_at'      => Carbon::now(),
            'push_enabled'           => true,
            'watchlist_push_enabled' => true,
            'watch_keywords'         => ['tesla'],
        ]);
        $user->deviceTokens()->create(['platform' => 'android', 'token' => 'fcm-1']);
        $topic = $user->topics()->create(['name' => 'Cars', 'position' => 0]);

        $provider = (new FakeArticleProvider())->withFixed([
            ['Tesla announces new battery', 'https://e.test/t1'],
        ]);

        $this->refresher($provider)->refresh($topic);

        $this->assertCount(0, $this->fakeSender->sent);
    }

    public function test_pushes_are_capped_per_refresh(): void
    {
        $user = $this->proWatcher(['story']);
        $topic = $user->topics()->create(['name' => 'Firehose', 'position' => 0]);

        // Every article matches "story" — only the cap (3) should be pushed.
        $provider = (new FakeArticleProvider())->withFixed([
            ['Story one', 'https://e.test/1'],
            ['Story two', 'https://e.test/2'],
            ['Story three', 'https://e.test/3'],
            ['Story four', 'https://e.test/4'],
            ['Story five', 'https://e.test/5'],
        ]);

        $this->refresher($provider)->refresh($topic);

        $this->assertCount(3, $this->fakeSender->sent);
    }

    public function test_second_refresh_with_no_new_articles_pushes_nothing(): void
    {
        $user = $this->proWatcher(['tesla']);
        $topic = $user->topics()->create(['name' => 'Cars', 'position' => 0]);

        $provider = (new FakeArticleProvider())->withFixed([
            ['Tesla announces new battery', 'https://e.test/t1'],
        ]);

        $refresher = $this->refresher($provider);
        $refresher->refresh($topic);
        $this->assertCount(1, $this->fakeSender->sent);

        // Same fixed set again — nothing new inserted, so no second push.
        $refresher->refresh($topic->fresh());
        $this->assertCount(1, $this->fakeSender->sent);
    }

    public function test_watchlist_push_toggle_persists_via_api_preferences(): void
    {
        $user = $this->proWatcher();
        \Laravel\Sanctum\Sanctum::actingAs($user);

        $this->putJson('/api/preferences', [
            'refresh_hour' => 6, 'timezone' => 'UTC',
            'digest_enabled' => false, 'digest_new_only' => false,
            'watchlist_push_enabled' => false,
        ])->assertOk()->assertJsonPath('user.watchlist_push_enabled', false);

        $this->assertFalse($user->fresh()->watchlist_push_enabled);
    }
}
