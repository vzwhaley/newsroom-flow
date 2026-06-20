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
}
