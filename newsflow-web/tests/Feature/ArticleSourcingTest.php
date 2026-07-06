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

    public function test_parses_google_news_rss_into_real_articles(): void
    {
        config()->set('newsflow.sources.google_news.enabled', true);
        config()->set('newsflow.signals.hacker_news', false);

        $rss = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel>
  <item>
    <title>Afton council approves new riverfront park - Greeneville Sun</title>
    <link>https://news.google.com/rss/articles/ABC123?oc=5</link>
    <pubDate>Mon, 06 Jul 2026 12:00:00 GMT</pubDate>
    <description>&lt;a href="https://greenevillesun.com/x"&gt;Afton council approves new riverfront park&lt;/a&gt;</description>
    <source url="https://www.greenevillesun.com">Greeneville Sun</source>
  </item>
  <item>
    <title>Storms roll through Greene County overnight - WJHL</title>
    <link>https://news.google.com/rss/articles/DEF456?oc=5</link>
    <pubDate>Mon, 06 Jul 2026 11:00:00 GMT</pubDate>
    <description>Coverage of the overnight storms.</description>
    <source url="https://www.wjhl.com">WJHL</source>
  </item>
</channel></rss>
XML;

        Http::fake([
            'news.google.com/*' => Http::response($rss, 200, ['Content-Type' => 'application/xml']),
        ]);

        $articles = $this->hybrid()->fetch('Afton, Tennessee', 12);

        $this->assertCount(2, $articles);
        $headlines = array_map(fn ($a) => $a->headline, $articles);
        // Publisher suffix is stripped from the Google News title.
        $this->assertContains('Afton council approves new riverfront park', $headlines);
        $this->assertContains('Storms roll through Greene County overnight', $headlines);

        $first = collect($articles)->firstWhere('headline', 'Afton council approves new riverfront park');
        $this->assertSame('Greeneville Sun', $first->source);
        $this->assertStringContainsString('news.google.com', $first->url); // real, clickable link (not .example)
        $this->assertStringNotContainsString('<a', $first->description);     // HTML stripped
    }

    public function test_merges_same_story_across_sources_preferring_direct_url(): void
    {
        // Two sources return the SAME story: one an aggregator (google) redirect
        // link with a full description, the other a DIRECT publisher URL with an
        // image. They should collapse into ONE enriched article.
        config()->set('newsflow.sources.thenewsapi.key', 'k1');
        config()->set('newsflow.sources.gnews.key', 'k2');
        config()->set('newsflow.signals.hacker_news', false);

        $headline = 'Major breaking news story about the national economy';

        Http::fake([
            'api.thenewsapi.com/*' => Http::response(['data' => [[
                'title'        => $headline,
                'description'  => 'A full description of the economic story.',
                'url'          => 'https://news.google.com/rss/articles/ABC123',
                'image_url'    => null,
                'published_at' => '2026-07-06T08:00:00Z',
            ]]]),
            'gnews.io/*' => Http::response(['articles' => [[
                'title'       => $headline,
                'description' => '',
                'url'         => 'https://realpublisher.example/economy-story',
                'image'       => 'https://realpublisher.example/img.jpg',
                'source'      => ['name' => 'Real Publisher'],
                'publishedAt' => '2026-07-06T08:05:00Z',
            ]]]),
        ]);

        $articles = $this->hybrid()->fetch('economy', 12);

        $this->assertCount(1, $articles); // merged, not duplicated
        $a = $articles[0];
        $this->assertStringContainsString('realpublisher.example', $a->url); // direct URL preferred
        $this->assertSame('https://realpublisher.example/img.jpg', $a->imageUrl); // image kept
        $this->assertSame('A full description of the economic story.', $a->description); // description filled
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

    public function test_llm_rewrites_descriptions_when_enabled(): void
    {
        config()->set('newsflow.sources.thenewsapi.key', 'test-key');
        config()->set('newsflow.llm.enabled', true);
        config()->set('newsflow.llm.api_key', 'sk-test');
        config()->set('newsflow.signals.hacker_news', false);

        Http::fake([
            'api.thenewsapi.com/*' => Http::response([
                'data' => [
                    ['title' => 'One', 'description' => 'Original long description one.', 'url' => 'https://n.test/1', 'published_at' => '2026-06-15T08:00:00Z'],
                    ['title' => 'Two', 'description' => 'Original long description two.', 'url' => 'https://n.test/2', 'published_at' => '2026-06-15T09:00:00Z'],
                ],
            ]),
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => '[{"i":0,"summary":"Crisp summary one."},{"i":1,"summary":"Crisp summary two."}]',
                ]],
            ]),
        ]);

        $articles = $this->hybrid()->fetch('World News', 12);

        // Both descriptions are replaced by the LLM's crisp summaries (applied
        // by index; exact pairing depends on ranking order, which is fine).
        $descriptions = collect($articles)->pluck('description')->sort()->values()->all();
        $this->assertSame(['Crisp summary one.', 'Crisp summary two.'], $descriptions);
        foreach ($articles as $a) {
            $this->assertStringNotContainsString('Original', $a->description);
        }
    }

    public function test_llm_failure_keeps_original_descriptions(): void
    {
        config()->set('newsflow.sources.thenewsapi.key', 'test-key');
        config()->set('newsflow.llm.enabled', true);
        config()->set('newsflow.llm.api_key', 'sk-test');
        config()->set('newsflow.signals.hacker_news', false);

        Http::fake([
            'api.thenewsapi.com/*' => Http::response([
                'data' => [
                    ['title' => 'One', 'description' => 'Keep me.', 'url' => 'https://n.test/1', 'published_at' => '2026-06-15T08:00:00Z'],
                ],
            ]),
            'api.anthropic.com/*' => Http::response('error', 500),
        ]);

        $articles = $this->hybrid()->fetch('World News', 12);

        $this->assertSame('Keep me.', $articles[0]->description);
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
