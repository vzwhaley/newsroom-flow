<?php

namespace Tests\Feature;

use App\Contracts\ArticleProvider;
use App\Models\User;
use App\Services\Articles\TopicRefresher;
use App\Support\FetchedArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProPowerFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function pro(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at'     => Carbon::now(),
            'lifetime_purchased_at' => Carbon::now(),
        ], $attrs));
    }

    // --- Blocked sources ---

    public function test_blocked_publisher_articles_are_filtered_for_pro(): void
    {
        $user = $this->pro(['blocked_sources' => ['blockedco']]);
        $topic = $user->topics()->create(['name' => 'Tech', 'position' => 0]);

        $provider = new class implements ArticleProvider {
            public function fetch(string $topic, int $count, array $excludeFingerprints = []): array
            {
                return [
                    new FetchedArticle('Good story', 'x', 'https://good.example/1', 'Good News'),
                    new FetchedArticle('Blocked story', 'x', 'https://blockedco.example/2', 'BlockedCo News'),
                ];
            }
        };

        (new TopicRefresher($provider))->refresh($topic);

        $headlines = $topic->articles()->pluck('headline')->all();
        $this->assertContains('Good story', $headlines);
        $this->assertNotContains('Blocked story', $headlines);
    }

    public function test_blocked_sources_are_ignored_for_free_users(): void
    {
        $user = User::factory()->create(['blocked_sources' => ['blockedco']]); // free
        $topic = $user->topics()->create(['name' => 'Tech', 'position' => 0]);

        $provider = new class implements ArticleProvider {
            public function fetch(string $topic, int $count, array $excludeFingerprints = []): array
            {
                return [new FetchedArticle('Blocked story', 'x', 'https://blockedco.example/2', 'BlockedCo News')];
            }
        };

        (new TopicRefresher($provider))->refresh($topic);

        $this->assertContains('Blocked story', $topic->articles()->pluck('headline')->all());
    }

    // --- Watchlist ---

    public function test_dashboard_surfaces_watchlist_hits_for_pro(): void
    {
        $user = $this->pro(['watch_keywords' => ['merger']]);
        $topic = $user->topics()->create(['name' => 'Business', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Huge merger announced', 'description' => 'x', 'url' => 'https://e.test/1', 'fingerprint' => '1', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Unrelated news', 'description' => 'x', 'url' => 'https://e.test/2', 'fingerprint' => '2', 'position' => 1]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->has('watchlist', 1)
            ->where('watchlist.0.headline', 'Huge merger announced')
            ->where('watchlist.0.matches.0', 'merger')
        );
    }

    public function test_free_user_has_no_watchlist(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'watch_keywords' => ['merger']]);
        $topic = $user->topics()->create(['name' => 'Business', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Huge merger announced', 'description' => 'x', 'url' => 'https://e.test/1', 'fingerprint' => '1', 'position' => 0]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($page) => $page->has('watchlist', 0));
    }

    // --- Search ---

    public function test_pro_user_can_search_feeds_and_saved(): void
    {
        $user = $this->pro();
        $topic = $user->topics()->create(['name' => 'Tech', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Apple ships a new chip', 'description' => 'x', 'url' => 'https://e.test/a', 'fingerprint' => 'a', 'position' => 0]);
        $topic->articles()->create(['headline' => 'Banana prices fall', 'description' => 'x', 'url' => 'https://e.test/b', 'fingerprint' => 'b', 'position' => 1]);
        $user->savedArticles()->create(['headline' => 'Apple Vision review', 'description' => 'x', 'url' => 'https://e.test/c', 'fingerprint' => 'c']);

        $this->actingAs($user)->get(route('search', ['q' => 'apple']))->assertInertia(fn ($page) => $page
            ->component('Search')
            ->where('locked', false)
            ->has('feed', 1)
            ->where('feed.0.headline', 'Apple ships a new chip')
            ->has('saved', 1)
        );
    }

    public function test_search_is_locked_for_free_users(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $this->actingAs($user)->get(route('search', ['q' => 'apple']))->assertInertia(fn ($page) => $page
            ->component('Search')
            ->where('locked', true)
            ->has('feed', 0)
        );
    }
}
