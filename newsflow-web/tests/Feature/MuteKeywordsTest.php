<?php

namespace Tests\Feature;

use App\Contracts\ArticleProvider;
use App\Models\Topic;
use App\Models\User;
use App\Services\Articles\TopicRefresher;
use App\Support\FetchedArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MuteKeywordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_muted_keyword_articles_are_filtered_during_refresh(): void
    {
        $user = User::factory()->create(['lifetime_purchased_at' => Carbon::now()]);
        $topic = $user->topics()->create([
            'name'          => 'NFL',
            'position'      => 0,
            'mute_keywords' => ['injury'],
        ]);

        // Provider returns a mix; one headline contains the muted word.
        $provider = new class implements ArticleProvider {
            public function fetch(string $topic, int $count, array $excludeFingerprints = []): array
            {
                return [
                    new FetchedArticle('Team wins big', 'A great game', 'https://x.test/1'),
                    new FetchedArticle('Star player injury update', 'Bad news', 'https://x.test/2'),
                    new FetchedArticle('Trade rumors swirl', 'Gossip', 'https://x.test/3'),
                ];
            }
        };

        (new TopicRefresher($provider))->refresh($topic);

        $headlines = $topic->articles()->pluck('headline')->all();
        $this->assertContains('Team wins big', $headlines);
        $this->assertContains('Trade rumors swirl', $headlines);
        $this->assertNotContains('Star player injury update', $headlines);
    }

    public function test_pro_user_can_update_mutes_and_existing_matches_are_purged(): void
    {
        $this->app->instance(ArticleProvider::class, new class implements ArticleProvider {
            public function fetch(string $topic, int $count, array $excludeFingerprints = []): array
            {
                return [];
            }
        });

        $user = User::factory()->create([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ]);
        $topic = $user->topics()->create(['name' => 'NFL', 'position' => 0]);
        $topic->articles()->create([
            'headline' => 'Major injury report', 'description' => 'x',
            'url' => 'https://x.test/a', 'fingerprint' => 'a', 'position' => 0,
        ]);
        $topic->articles()->create([
            'headline' => 'Game recap', 'description' => 'x',
            'url' => 'https://x.test/b', 'fingerprint' => 'b', 'position' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('topics.mutes', $topic), ['mute_keywords' => ['injury']])
            ->assertRedirect();

        $headlines = $topic->fresh()->articles()->pluck('headline')->all();
        $this->assertNotContains('Major injury report', $headlines);
        $this->assertContains('Game recap', $headlines);
    }

    public function test_free_user_cannot_set_mutes(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'NFL', 'position' => 0]);

        $this->actingAs($user)
            ->patch(route('topics.mutes', $topic), ['mute_keywords' => ['injury']])
            ->assertSessionHas('error');

        $this->assertEmpty($topic->fresh()->mute_keywords ?? []);
    }
}
