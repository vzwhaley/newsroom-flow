<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DailyBriefingTest extends TestCase
{
    use RefreshDatabase;

    private function proUserWithArticles(): User
    {
        $user = User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);

        $topic = $user->topics()->create(['name' => 'Space', 'position' => 0]);
        $topic->articles()->create([
            'headline' => 'New rocket reaches orbit', 'description' => 'A big launch.',
            'url' => 'https://e.test/rocket', 'fingerprint' => 'r1', 'position' => 0, 'fetched_at' => Carbon::now(),
        ]);

        return $user;
    }

    public function test_briefing_is_a_pro_feature(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)->getJson('/briefing')->assertForbidden();

        Sanctum::actingAs($user);
        $this->getJson('/api/briefing')->assertForbidden();
    }

    public function test_briefing_without_llm_uses_deterministic_fallback_and_caches(): void
    {
        config()->set('newsroomflow.llm.enabled', false);

        $user = $this->proUserWithArticles();

        $first = $this->actingAs($user)->getJson('/briefing')
            ->assertOk()
            ->assertJsonPath('ai', false)
            ->assertJsonPath('cached', false)
            ->json();

        $this->assertStringContainsString('Space', $first['briefing']);
        $this->assertStringContainsString('New rocket reaches orbit', $first['briefing']);

        // Second call the same day returns the cached text.
        $this->actingAs($user)->getJson('/briefing')
            ->assertOk()
            ->assertJsonPath('cached', true)
            ->assertJsonPath('briefing', $first['briefing']);
    }

    public function test_briefing_calls_claude_when_configured(): void
    {
        config()->set('newsroomflow.llm.enabled', true);
        config()->set('newsroomflow.llm.api_key', 'test-key');

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Your newsroom leads with the rocket launch.']],
            ]),
        ]);

        $user = $this->proUserWithArticles();

        $this->actingAs($user)->getJson('/briefing')
            ->assertOk()
            ->assertJsonPath('ai', true)
            ->assertJsonPath('briefing', 'Your newsroom leads with the rocket launch.');

        Http::assertSentCount(1);

        // Cached — no second LLM call.
        $this->actingAs($user)->getJson('/briefing')->assertOk()->assertJsonPath('cached', true);
        Http::assertSentCount(1);
    }

    public function test_briefing_with_no_articles_is_404(): void
    {
        $user = User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);

        $this->actingAs($user)->getJson('/briefing')->assertNotFound();
    }

    public function test_api_briefing_matches_web_semantics(): void
    {
        config()->set('newsroomflow.llm.enabled', false);

        $user = $this->proUserWithArticles();
        Sanctum::actingAs($user);

        $this->getJson('/api/briefing')
            ->assertOk()
            ->assertJsonStructure(['briefing', 'ai', 'date', 'cached']);
    }
}
