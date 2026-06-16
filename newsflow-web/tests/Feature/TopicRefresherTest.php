<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Articles\TopicRefresher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeArticleProvider;
use Tests\TestCase;

class TopicRefresherTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_refresh_fills_topic_to_twelve(): void
    {
        $user = User::factory()->create();
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);

        $refresher = new TopicRefresher(new FakeArticleProvider());
        $stats = $refresher->refresh($topic);

        $this->assertSame(12, $topic->articles()->count());
        $this->assertSame(12, $stats['added']);
        $this->assertSame(0, $stats['dropped']);
    }

    public function test_new_articles_prepend_and_drop_oldest_keeping_twelve(): void
    {
        $user = User::factory()->create();
        $topic = $user->topics()->create(['name' => 'World News', 'position' => 0]);

        $provider = new FakeArticleProvider();

        // First fill: articles 1..12 (cursor advances).
        (new TopicRefresher($provider))->refresh($topic);
        $this->assertSame(12, $topic->articles()->count());

        $oldestHeadlineBefore = $topic->articles()->orderByDesc('position')->first()->headline;
        $topTopBefore = $topic->articles()->orderBy('position')->first()->headline;

        // Second refresh: provider returns 12 brand-new articles (13..24).
        $stats = (new TopicRefresher($provider))->refresh($topic->fresh());

        // Still exactly 12.
        $this->assertSame(12, $topic->articles()->count());
        $this->assertSame(12, $stats['added']);
        $this->assertSame(12, $stats['dropped']);

        // The newest batch is now on top; the previous oldest is gone.
        $newTop = $topic->articles()->orderBy('position')->first()->headline;
        $this->assertNotSame($topTopBefore, $newTop);
        $this->assertDatabaseMissing('articles', [
            'topic_id' => $topic->id,
            'headline' => $oldestHeadlineBefore,
        ]);
    }

    public function test_partial_new_batch_keeps_twelve_and_drops_equal_count(): void
    {
        $user = User::factory()->create();
        $topic = $user->topics()->create(['name' => 'Niche', 'position' => 0]);

        // Reuse ONE provider so its cursor keeps advancing — the second batch
        // is genuinely new (articles 13..15), not a repeat of the first fill.
        $provider = new FakeArticleProvider();

        // Fill with 12 first (articles 1..12).
        (new TopicRefresher($provider))->refresh($topic);

        // Now only 3 new stories surface (articles 13..15).
        $provider->alwaysReturnNew(3);
        $stats = (new TopicRefresher($provider))->refresh($topic->fresh());

        $this->assertSame(12, $topic->articles()->count());
        $this->assertSame(3, $stats['added']);
        $this->assertSame(3, $stats['dropped']);
    }

    public function test_no_new_articles_preserves_existing_feed(): void
    {
        $user = User::factory()->create();
        $topic = $user->topics()->create(['name' => 'Indiana Jones', 'position' => 0]);

        // A niche topic that always returns the SAME 3 stories.
        $provider = (new FakeArticleProvider())->withFixed([
            ['Story A', 'https://example.test/a'],
            ['Story B', 'https://example.test/b'],
            ['Story C', 'https://example.test/c'],
        ]);

        $first = (new TopicRefresher($provider))->refresh($topic);
        $this->assertSame(3, $topic->articles()->count());
        $this->assertSame(3, $first['added']);

        // Re-running finds nothing new — feed is preserved, none dropped.
        $second = (new TopicRefresher($provider))->refresh($topic->fresh());
        $this->assertSame(3, $topic->articles()->count());
        $this->assertSame(0, $second['added']);
        $this->assertSame(0, $second['dropped']);
    }
}
