<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleActionsTest extends TestCase
{
    use RefreshDatabase;

    private function articleFor(User $user): Article
    {
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);

        return $topic->articles()->create([
            'headline'    => 'Something happened today',
            'description' => 'A short blurb.',
            'url'         => 'https://news.example/story',
            'fingerprint' => 'fp-'.uniqid(),
            'position'    => 0,
        ]);
    }

    private function pro(): User
    {
        return User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);
    }

    private function enableLlm(): void
    {
        config()->set('newspaperflow.llm.enabled', true);
        config()->set('newspaperflow.llm.api_key', 'sk-test');
    }

    public function test_pro_user_gets_a_tldr_summary(): void
    {
        $this->enableLlm();
        Http::fake([
            'news.example/*'   => Http::response('<html><body>'.str_repeat('Real article body text. ', 60).'</body></html>'),
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'A crisp two sentence summary. It tells you what happened.']]]),
        ]);

        $user = $this->pro();
        $article = $this->articleFor($user);

        $res = $this->actingAs($user)->postJson(route('articles.summary', $article));

        $res->assertOk()->assertJsonPath('tldr', 'A crisp two sentence summary. It tells you what happened.');
        $this->assertNotNull($article->fresh()->tldr);
    }

    public function test_tldr_is_cached_after_first_generation(): void
    {
        $this->enableLlm();
        Http::fake([
            'news.example/*'   => Http::response('<html><body>'.str_repeat('Body. ', 80).'</body></html>'),
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Cached me.']]]),
        ]);

        $user = $this->pro();
        $article = $this->articleFor($user);

        $this->actingAs($user)->postJson(route('articles.summary', $article))->assertOk();
        $this->actingAs($user)->postJson(route('articles.summary', $article))
            ->assertOk()
            ->assertJsonPath('cached', true);

        // Only one Anthropic call total — the second was served from cache.
        Http::assertSentCount(2); // 1 article fetch + 1 anthropic, from the first request only
    }

    public function test_free_user_cannot_get_tldr(): void
    {
        $this->enableLlm();
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $article = $this->articleFor($user);

        $this->actingAs($user)->postJson(route('articles.summary', $article))->assertForbidden();
    }

    public function test_cannot_summarize_another_users_article(): void
    {
        $owner = $this->pro();
        $article = $this->articleFor($owner);
        $intruder = $this->pro();

        $this->actingAs($intruder)->postJson(route('articles.summary', $article))->assertForbidden();
    }

    public function test_mark_read_and_unread(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $article = $this->articleFor($user);

        $this->actingAs($user)->postJson(route('articles.read', $article))->assertOk();
        $this->assertNotNull($article->fresh()->read_at);

        $this->actingAs($user)->deleteJson(route('articles.unread', $article))->assertOk();
        $this->assertNull($article->fresh()->read_at);
    }

    public function test_mark_all_read_for_a_topic(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);
        $topic->articles()->create(['headline' => 'A', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0]);
        $topic->articles()->create(['headline' => 'B', 'description' => 'x', 'url' => 'https://e.test/b', 'fingerprint' => 'b', 'position' => 1]);

        $this->actingAs($user)->post(route('topics.read-all', $topic))->assertRedirect();

        $this->assertSame(0, $topic->articles()->whereNull('read_at')->count());
    }

    public function test_cannot_mark_another_users_article_read(): void
    {
        $owner = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $article = $this->articleFor($owner);
        $intruder = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($intruder)->postJson(route('articles.read', $article))->assertForbidden();
    }
}
