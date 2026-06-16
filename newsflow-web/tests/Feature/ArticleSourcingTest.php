<?php

namespace Tests\Feature;

use App\Services\Articles\HybridArticleProvider;
use App\Services\Articles\Signals\HackerNewsSignal;
use App\Services\Articles\StubArticleProvider;
use App\Support\FetchedArticle;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleSourcingTest extends TestCase
{
    private function hybrid(): HybridArticleProvider
    {
        return new HybridArticleProvider(new StubArticleProvider(), new HackerNewsSignal());
    }

    public function test_hacker_news_signal_boosts_matching_candidate(): void
    {
        Http::fake([
            'hn.algolia.com/*' => Http::response([
                'hits' => [
                    ['url' => 'https://example.com/big-story', 'points' => 500, 'num_comments' => 200],
                ],
            ]),
        ]);

        $match = new FetchedArticle('Big Story', 'd', 'https://www.example.com/big-story', popularityScore: 50);
        $other = new FetchedArticle('Other', 'd', 'https://example.com/other', popularityScore: 50);

        $result = (new HackerNewsSignal())->boost('anything', [$match, $other]);

        $this->assertGreaterThan(50, $result[0]->popularityScore); // boosted
        $this->assertSame(50.0, $result[1]->popularityScore);       // untouched
    }

    public function test_signal_is_best_effort_on_http_failure(): void
    {
        Http::fake(['hn.algolia.com/*' => Http::response('', 500)]);

        $a = new FetchedArticle('A', 'd', 'https://example.com/a', popularityScore: 42);
        $result = (new HackerNewsSignal())->boost('x', [$a]);

        $this->assertSame(42.0, $result[0]->popularityScore);
    }

    public function test_falls_back_to_stub_when_no_sources_configured(): void
    {
        config()->set('newsflow.sources.thenewsapi.key', null);
        config()->set('newsflow.sources.newsdata.key', null);
        config()->set('newsflow.sources.gnews.key', null);
        config()->set('newsflow.sources.newsapi.key', null);

        $articles = $this->hybrid()->fetch('World News', 12);

        $this->assertCount(12, $articles);
        $this->assertInstanceOf(FetchedArticle::class, $articles[0]);
    }

    public function test_parses_thenewsapi_and_returns_articles(): void
    {
        config()->set('newsflow.sources.thenewsapi.key', 'test-key');
        config()->set('newsflow.signals.hacker_news', true);

        Http::fake([
            'api.thenewsapi.com/*' => Http::response([
                'data' => [
                    [
                        'title'        => 'Real Headline One',
                        'description'  => 'A real description.',
                        'url'          => 'https://news.example/one',
                        'source'       => 'news.example',
                        'image_url'    => 'https://news.example/one.jpg',
                        'published_at' => '2026-06-15T08:00:00Z',
                    ],
                    [
                        'title'        => 'Real Headline Two',
                        'snippet'      => 'Snippet two.',
                        'url'          => 'https://news.example/two',
                        'source'       => 'news.example',
                        'published_at' => '2026-06-15T09:00:00Z',
                    ],
                ],
            ]),
            'hn.algolia.com/*' => Http::response(['hits' => []]),
        ]);

        $articles = $this->hybrid()->fetch('World News', 12);

        $this->assertCount(2, $articles);
        $headlines = array_map(fn ($a) => $a->headline, $articles);
        $this->assertContains('Real Headline One', $headlines);
        $this->assertContains('Real Headline Two', $headlines);
    }

    public function test_dedupes_same_story_across_sources(): void
    {
        config()->set('newsflow.sources.thenewsapi.key', 'test-key');

        Http::fake([
            'api.thenewsapi.com/*' => Http::response([
                'data' => [
                    ['title' => 'Dupe', 'description' => 'd', 'url' => 'https://x.example/story', 'published_at' => '2026-06-15T08:00:00Z'],
                    ['title' => 'Dupe (mirror)', 'description' => 'd', 'url' => 'https://www.x.example/story/', 'published_at' => '2026-06-15T08:00:00Z'],
                ],
            ]),
            'hn.algolia.com/*' => Http::response(['hits' => []]),
        ]);

        $articles = $this->hybrid()->fetch('Topic', 12);

        // Both URLs canonicalize to x.example/story → one article.
        $this->assertCount(1, $articles);
    }
}
