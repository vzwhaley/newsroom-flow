<?php

namespace Tests\Support;

use App\Contracts\ArticleProvider;
use App\Support\FetchedArticle;
use Illuminate\Support\Carbon;

/**
 * Deterministic, controllable ArticleProvider for tests. Each fetch() returns
 * a fresh batch of unique articles (numbered from an internal cursor) so we can
 * exercise the "prepend new, drop oldest" refresh logic precisely.
 */
class FakeArticleProvider implements ArticleProvider
{
    private int $cursor = 0;

    /** @var array<int, FetchedArticle>|null Fixed set to always return, if set. */
    private ?array $fixed = null;

    public function __construct(private int $batchSize = 12)
    {
    }

    /**
     * Force fetch() to always return exactly this many brand-new articles,
     * regardless of the requested count (useful for "N new today" scenarios).
     */
    public function alwaysReturnNew(int $count): self
    {
        $this->batchSize = $count;

        return $this;
    }

    /**
     * Return a fixed set every call (e.g. to simulate a niche topic with the
     * same handful of stories and nothing new).
     *
     * @param  array<int, array{0:string,1:string}>  $items  [headline, url] pairs
     */
    public function withFixed(array $items): self
    {
        $this->fixed = array_map(fn ($i) => new FetchedArticle(
            headline: $i[0],
            description: 'desc',
            url: $i[1],
            source: 'Test Source',
            publishedAt: Carbon::now(),
            popularityScore: 50,
        ), $items);

        return $this;
    }

    public function fetch(string $topic, int $count, array $excludeFingerprints = []): array
    {
        if ($this->fixed !== null) {
            return $this->fixed;
        }

        $out = [];
        $n = $this->batchSize ?: $count;

        for ($i = 0; $i < $n; $i++) {
            $this->cursor++;
            $out[] = new FetchedArticle(
                headline: "Article {$this->cursor}",
                description: "Description for article {$this->cursor}",
                url: "https://example.test/{$topic}/{$this->cursor}",
                source: 'Test Source',
                publishedAt: Carbon::now()->subMinutes($this->cursor),
                popularityScore: 100 - $i,
            );
        }

        return $out;
    }
}
