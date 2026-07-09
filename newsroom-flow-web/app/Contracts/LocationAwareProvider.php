<?php

namespace App\Contracts;

use App\Support\FetchedArticle;

/**
 * An ArticleProvider that can bias results toward a geography — a country and
 * an optional set of local-outlet domains — for local-area news feeds.
 *
 * Kept separate from ArticleProvider so simple providers (the stub, test
 * fakes) don't have to implement it; TopicRefresher checks `instanceof` and
 * falls back to the plain fetch() when a provider isn't location-aware.
 */
interface LocationAwareProvider
{
    /**
     * Like fetch(), but scoped to a place.
     *
     * @param  string  $query  the geocoded location query (e.g. '"Cleveland" Ohio')
     * @param  array<string>  $excludeFingerprints
     * @param  string|null  $country  ISO-3166 alpha-2, lowercase (e.g. 'us', 'gb')
     * @param  array<string>  $domains  curated local outlets to bias toward
     * @return array<int, FetchedArticle>
     */
    public function fetchLocal(
        string $query,
        int $count,
        array $excludeFingerprints = [],
        ?string $country = null,
        array $domains = [],
    ): array;
}
