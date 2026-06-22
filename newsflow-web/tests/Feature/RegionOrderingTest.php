<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegionOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_region_classifier_ranks_publishers(): void
    {
        // american (0) < european (1) < asian (2) < other (3)
        $this->assertSame(0, Region::priority('NPR', 'https://www.npr.org/x'));
        $this->assertSame(0, Region::priority(null, 'https://www.cbc.ca/news/x')); // Canada → American
        $this->assertSame(1, Region::priority('BBC News', 'https://www.bbc.com/news/x'));
        $this->assertSame(1, Region::priority(null, 'https://www.lemonde.fr/x'));   // .fr → European
        $this->assertSame(2, Region::priority('Times of India', 'https://timesofindia.indiatimes.com/x'));
        $this->assertSame(2, Region::priority(null, 'https://example.co.jp/x'));    // .jp → Asian
        $this->assertSame(3, Region::priority('Unknown Blog', 'https://random.example/x')); // unknown last
    }

    public function test_feed_orders_articles_american_european_asian_via_api(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);

        // Stored newest-first by position: European is "newest" (pos 0), then
        // American (1), then Asian (2). Region ordering must override that.
        $topic->articles()->create(['headline' => 'EU story', 'description' => 'x', 'url' => 'https://www.bbc.com/news/eu', 'source' => 'BBC News', 'fingerprint' => 'eu', 'position' => 0]);
        $topic->articles()->create(['headline' => 'US story', 'description' => 'x', 'url' => 'https://www.npr.org/us', 'source' => 'NPR', 'fingerprint' => 'us', 'position' => 1]);
        $topic->articles()->create(['headline' => 'Asia story', 'description' => 'x', 'url' => 'https://timesofindia.indiatimes.com/asia', 'source' => 'Times of India', 'fingerprint' => 'as', 'position' => 2]);

        Sanctum::actingAs($user);

        $this->getJson('/api/feed')->assertOk()
            ->assertJsonPath('topics.0.articles.0.headline', 'US story')   // American first
            ->assertJsonPath('topics.0.articles.1.headline', 'EU story')   // European next
            ->assertJsonPath('topics.0.articles.2.headline', 'Asia story'); // Asian last
    }

    public function test_within_region_newest_position_wins(): void
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);

        // Two American sources: position 0 (newer) should precede position 1.
        $topic->articles()->create(['headline' => 'US newer', 'description' => 'x', 'url' => 'https://www.cnn.com/a', 'source' => 'CNN', 'fingerprint' => 'a', 'position' => 0]);
        $topic->articles()->create(['headline' => 'US older', 'description' => 'x', 'url' => 'https://www.foxnews.com/b', 'source' => 'Fox News', 'fingerprint' => 'b', 'position' => 1]);

        Sanctum::actingAs($user);

        $this->getJson('/api/feed')->assertOk()
            ->assertJsonPath('topics.0.articles.0.headline', 'US newer')
            ->assertJsonPath('topics.0.articles.1.headline', 'US older');
    }
}
