<?php

namespace App\Contracts;

use App\Support\FetchedArticle;

interface ArticleProvider
{
    /**
     * Scour available sources for the most-read / most-popular recent
     * articles on a topic and return up to $count candidates, ordered
     * best-first (freshest + most popular).
     *
     * Implementations should keep widening their search (older windows,
     * adjacent phrasings, more sources) until they can return $count items
     * where the topic supports it. Niche topics may legitimately return
     * fewer — callers must tolerate that.
     *
     * @param  array<string>  $excludeFingerprints  Stories the caller already
     *         has; providers should try to return genuinely new articles and
     *         avoid these, but may include them if nothing newer exists.
     * @return array<int, FetchedArticle>
     */
    public function fetch(string $topic, int $count, array $excludeFingerprints = []): array;
}
