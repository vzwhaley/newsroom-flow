<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Article Provider
    |--------------------------------------------------------------------------
    |
    | Which implementation of App\Contracts\ArticleProvider is used to
    | scour the internet for a topic's top stories. Options:
    |
    |   'hybrid' — News API for fresh coverage + social engagement signals
    |              for popularity ranking + an LLM to summarize, dedupe and
    |              guarantee a full set of articles. (Recommended.)
    |   'stub'   — Generates realistic placeholder articles with no network
    |              calls. Used for local development and tests so the whole
    |              app is clickable before any API keys are configured.
    |
    | The hybrid provider automatically falls back to the stub provider for
    | any source that is not yet configured, so the app always returns a
    | full feed.
    |
    */

    'provider' => env('NEWSFLOW_PROVIDER', 'hybrid'),

    /*
    |--------------------------------------------------------------------------
    | News aggregator APIs (fresh-coverage layer)
    |--------------------------------------------------------------------------
    |
    | The hybrid provider queries whichever of these has a key configured,
    | in order, merging and de-duplicating the results.
    |
    */

    'sources' => [
        // TheNewsAPI — cheapest real-time COMMERCIAL option ($19-49/mo),
        // supports sort=relevance_score, returns description + image + time.
        // Recommended primary source for launch.
        'thenewsapi' => [
            'key'      => env('THENEWSAPI_KEY'),
            'endpoint' => 'https://api.thenewsapi.com/v1/news/all',
        ],
        // NewsData.io — one of the few APIs whose FREE tier permits commercial
        // use (200 credits/day, 12h delay). 12h delay is fine for a "yesterday's
        // most-read" 6 AM batch, making this a genuine zero-cost launch source.
        'newsdata' => [
            'key'      => env('NEWSDATA_KEY'),
            'endpoint' => 'https://newsdata.io/api/1/news',
        ],
        // GNews — usable but free tier is non-commercial; paid €49.99/mo.
        'gnews' => [
            'key'      => env('GNEWS_KEY'),
            'endpoint' => 'https://gnews.io/api/v4/search',
        ],
        // NewsAPI.org — AVOID for production: free is 24h-delayed, localhost
        // only, non-commercial; first commercial tier is $449/mo.
        'newsapi' => [
            'key'      => env('NEWSAPI_KEY'),
            'endpoint' => 'https://newsapi.org/v2/everything',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Popularity signals (ranking layer)
    |--------------------------------------------------------------------------
    |
    | Public engagement signals used to approximate "most read / most
    | popular". True page-view counts are private to publishers, so we
    | blend these proxies into a popularity score.
    |
    */

    'signals' => [
        // Hacker News (Algolia) — keyless, free, commercial-clean, 10k req/hr.
        // The primary popularity signal. On by default.
        'hacker_news' => env('NEWSFLOW_SIGNAL_HN', true),
        // Bluesky firehose — free, commercial-clean secondary signal (count
        // how many posts link a candidate URL). Off until implemented.
        'bluesky'     => env('NEWSFLOW_SIGNAL_BLUESKY', false),
        // Reddit — intentionally OFF: 2025+ terms require a paid licence for
        // commercial use. Do not enable without a licensed Reddit API app.
        'reddit'      => env('NEWSFLOW_SIGNAL_REDDIT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM summarizer (summarize / dedupe / fill layer)
    |--------------------------------------------------------------------------
    |
    | Claude writes the headline + brief description for each article,
    | removes near-duplicate stories, and keeps widening the search until
    | a full set of articles is found (important for niche topics that may
    | not have 12 fresh stories on any given day).
    |
    */

    'llm' => [
        'enabled' => (bool) env('ANTHROPIC_API_KEY'),
        'api_key' => env('ANTHROPIC_API_KEY'),
        // Haiku is plenty for dedupe + one-line summaries and is the cheapest
        // option (~$5/mo at 1k topics/day via the Batches API).
        'model'   => env('NEWSFLOW_LLM_MODEL', 'claude-haiku-4-5-20251001'),
        'endpoint' => 'https://api.anthropic.com/v1/messages',
        'version'  => '2023-06-01',
    ],

    /*
    |--------------------------------------------------------------------------
    | Refresh behaviour
    |--------------------------------------------------------------------------
    */

    // How many fresh candidates to gather before ranking down to the final
    // set. Higher = better "most popular" accuracy, more API cost.
    'candidate_pool' => 40,

    // Daily refresh hour (local app timezone). The scheduled command runs
    // at this time; users on Pro can also trigger an on-demand refresh.
    'refresh_hour' => 6,
];
