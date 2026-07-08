<?php

namespace Tests\Feature;

use App\Services\Demo\WorldNewsFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorldNewsDemoTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFeed(int $count): WorldNewsFeed
    {
        return new class($count) extends WorldNewsFeed {
            public function __construct(private int $count)
            {
            }

            public function fetch(int $limit): array
            {
                $out = [];
                for ($i = 1; $i <= $this->count; $i++) {
                    $out[] = [
                        'headline'     => "World story {$i}",
                        'description'  => "Description {$i}",
                        'url'          => "https://news.example/world/{$i}",
                        'source'       => 'BBC News',
                        'published_at' => Carbon::now()->subMinutes($i)->toIso8601String(),
                    ];
                }

                return $out;
            }
        };
    }

    public function test_world_news_demo_is_public_and_lists_articles(): void
    {
        $this->app->instance(WorldNewsFeed::class, $this->fakeFeed(12));

        $this->get('/world-news')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WorldNews')
                ->where('topic', 'World News')
                ->has('articles', 12)
                ->has('articles.0', fn ($a) => $a
                    ->has('headline')
                    ->has('url')
                    ->has('source')
                    ->etc()
                )
            );
    }

    public function test_world_news_demo_caches_the_feed(): void
    {
        // First request populates the cache.
        $this->app->instance(WorldNewsFeed::class, $this->fakeFeed(12));
        $this->get('/world-news')->assertOk()->assertInertia(fn ($page) => $page->has('articles', 12));

        // A subsequent request must be served from cache, not re-fetch.
        $this->app->instance(WorldNewsFeed::class, new class extends WorldNewsFeed {
            public function fetch(int $limit): array
            {
                throw new \RuntimeException('feed should not be called when cached');
            }
        });

        $this->get('/world-news')->assertOk()->assertInertia(fn ($page) => $page->has('articles', 12));
    }
}
