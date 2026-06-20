<?php

namespace Tests\Feature\Api;

use App\Contracts\ArticleProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(ArticleProvider::class, new FakeArticleProvider());
    }

    public function test_register_returns_a_token_and_user(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name'     => 'Mobile User',
            'email'    => 'mobile@example.com',
            'password' => 'password123',
        ]);

        $res->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'plan', 'is_pro']]);
        $this->assertDatabaseHas('users', ['email' => 'mobile@example.com']);
    }

    public function test_login_returns_a_token(): void
    {
        User::factory()->create(['email' => 'a@b.com', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/auth/login', ['email' => 'a@b.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        User::factory()->create(['email' => 'a@b.com', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/auth/login', ['email' => 'a@b.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    public function test_protected_endpoints_require_a_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/feed')->assertUnauthorized();
    }

    public function test_me_and_feed_with_a_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $topic->articles()->create(['headline' => 'A', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.email', $user->email);

        $this->getJson('/api/feed')->assertOk()
            ->assertJsonPath('topics.0.name', 'World News')
            ->assertJsonPath('topics.0.articles.0.headline', 'A');
    }

    public function test_can_add_and_delete_a_topic_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/topics', ['name' => 'Technology'])->assertCreated();
        $topicId = $res->json('topic.id');
        $this->assertSame(12, $user->topics()->find($topicId)->articles()->count());

        $this->deleteJson("/api/topics/{$topicId}")->assertOk();
        $this->assertNull($user->topics()->find($topicId));
    }

    public function test_free_topic_cap_enforced_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $user->topics()->create(['name' => 'One', 'position' => 0]);
        $user->topics()->create(['name' => 'Two', 'position' => 1]);
        Sanctum::actingAs($user);

        $this->postJson('/api/topics', ['name' => 'Three'])->assertStatus(422);
    }

    public function test_mark_article_read_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $article = $topic->articles()->create(['headline' => 'A', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0]);
        Sanctum::actingAs($user);

        $this->postJson("/api/articles/{$article->id}/read")->assertOk()->assertJsonPath('is_read', true);
        $this->assertNotNull($article->fresh()->read_at);
    }

    public function test_pro_user_can_search_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'Tech', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Apple ships a chip', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0]);
        Sanctum::actingAs($user);

        $this->getJson('/api/search?q=apple')->assertOk()
            ->assertJsonPath('locked', false)
            ->assertJsonPath('feed.0.headline', 'Apple ships a chip');
    }

    public function test_search_is_locked_for_free_via_api(): void
    {
        Sanctum::actingAs(User::factory()->create(['email_verified_at' => Carbon::now()]));
        $this->getJson('/api/search?q=apple')->assertOk()->assertJsonPath('locked', true);
    }

    public function test_can_update_preferences_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        Sanctum::actingAs($user);

        $this->putJson('/api/preferences', [
            'refresh_hour' => 8, 'timezone' => 'America/Chicago', 'digest_enabled' => true, 'digest_new_only' => true,
        ])->assertOk()->assertJsonPath('user.refresh_hour', 8);

        $user->refresh();
        $this->assertSame(8, $user->refresh_hour);
        $this->assertTrue($user->digest_enabled);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->assertCount(1, $user->tokens);

        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/auth/logout')->assertOk();

        // The token row is revoked (so it can't authenticate future requests).
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_watchlist_and_blocked_sources_persist_via_preferences(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);
        Sanctum::actingAs($user);

        $this->putJson('/api/preferences', [
            'refresh_hour' => 6, 'timezone' => 'UTC', 'digest_enabled' => false, 'digest_new_only' => false,
            'watch_keywords' => ['Tesla', ' Tesla ', 'SpaceX'],
            'blocked_sources' => ['tabloid.example'],
        ])->assertOk()
            ->assertJsonPath('user.watch_keywords', ['Tesla', 'SpaceX']) // trimmed + de-duped
            ->assertJsonPath('user.blocked_sources', ['tabloid.example']);

        // /me reflects the saved lists.
        $this->getJson('/api/me')
            ->assertJsonPath('user.watch_keywords', ['Tesla', 'SpaceX'])
            ->assertJsonPath('user.blocked_sources', ['tabloid.example']);
    }

    public function test_preferences_without_power_lists_does_not_wipe_them(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'watch_keywords' => ['Tesla'],
            'blocked_sources' => ['tabloid.example'],
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/preferences', [
            'refresh_hour' => 9, 'timezone' => 'UTC', 'digest_enabled' => false, 'digest_new_only' => false,
        ])->assertOk();

        $user->refresh();
        $this->assertSame(['Tesla'], $user->watch_keywords);
        $this->assertSame(['tabloid.example'], $user->blocked_sources);
    }

    public function test_pro_can_set_topic_mutes_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'Tech', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Crypto soars', 'description' => 'bitcoin', 'url' => 'https://e.test/c', 'fingerprint' => 'c', 'position' => 0]);
        $topic->articles()->create(['headline' => 'New laptop', 'description' => 'x', 'url' => 'https://e.test/l', 'fingerprint' => 'l', 'position' => 1]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/topics/{$topic->id}/mutes", ['mute_keywords' => ['crypto']])
            ->assertOk()
            ->assertJsonPath('topic.mute_keywords', ['crypto']);

        // The matching article was purged.
        $this->assertSame(['crypto'], $topic->fresh()->mute_keywords);
        $this->assertNull($topic->articles()->where('fingerprint', 'c')->first());
    }

    public function test_free_user_cannot_set_topic_mutes_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'Tech', 'position' => 0]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/topics/{$topic->id}/mutes", ['mute_keywords' => ['crypto']])->assertStatus(403);
    }

    public function test_mark_all_read_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'World', 'position' => 0]);
        $topic->articles()->create(['headline' => 'A', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0]);
        $topic->articles()->create(['headline' => 'B', 'description' => 'x', 'url' => 'https://e.test/b', 'fingerprint' => 'b', 'position' => 1]);
        Sanctum::actingAs($user);

        $this->postJson("/api/topics/{$topic->id}/read-all")->assertOk()->assertJsonPath('marked', 2);
        $this->assertSame(0, $topic->articles()->whereNull('read_at')->count());
    }

    public function test_reorder_topics_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $a = $user->topics()->create(['name' => 'A', 'position' => 0]);
        $b = $user->topics()->create(['name' => 'B', 'position' => 1]);
        Sanctum::actingAs($user);

        $this->postJson('/api/topics/reorder', ['order' => [$b->id, $a->id]])->assertOk();

        $this->assertSame(0, $b->fresh()->position);
        $this->assertSame(1, $a->fresh()->position);
    }

    public function test_pro_can_browse_archive_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);
        $user->archivedArticles()->create([
            'topic_name' => 'Tech', 'headline' => 'Old chip news', 'description' => 'x',
            'url' => 'https://e.test/old', 'source' => 'Wire', 'fingerprint' => 'o1', 'archived_at' => Carbon::now(),
        ]);
        $user->archivedArticles()->create([
            'topic_name' => 'World', 'headline' => 'Election recap', 'description' => 'x',
            'url' => 'https://e.test/elec', 'source' => 'Beacon', 'fingerprint' => 'o2', 'archived_at' => Carbon::now(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/archive')->assertOk()
            ->assertJsonPath('locked', false)
            ->assertJsonCount(2, 'articles');

        $this->getJson('/api/archive?q=election')->assertOk()
            ->assertJsonCount(1, 'articles')
            ->assertJsonPath('articles.0.headline', 'Election recap');
    }

    public function test_archive_is_locked_for_free_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $user->archivedArticles()->create([
            'topic_name' => 'Tech', 'headline' => 'Old', 'description' => 'x',
            'url' => 'https://e.test/old', 'fingerprint' => 'o1', 'archived_at' => Carbon::now(),
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/archive')->assertOk()
            ->assertJsonPath('locked', true)
            ->assertJsonCount(0, 'articles');
    }

    public function test_topic_mutes_require_authorization(): void
    {
        $owner = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);
        $topic = $owner->topics()->create(['name' => 'Tech', 'position' => 0]);

        $intruder = User::factory()->create(['email_verified_at' => Carbon::now(), 'lifetime_purchased_at' => Carbon::now()]);
        Sanctum::actingAs($intruder);

        $this->patchJson("/api/topics/{$topic->id}/mutes", ['mute_keywords' => ['x']])->assertStatus(403);
    }
}
