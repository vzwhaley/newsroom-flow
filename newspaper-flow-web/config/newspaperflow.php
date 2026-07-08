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

    'provider' => env('NEWSPAPERFLOW_PROVIDER', 'hybrid'),

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
        // Google News RSS — FREE, keyless, no signup. Scrapes Google News'
        // public RSS search feed for real, live articles on any topic (and any
        // locality for local-area feeds). This is the default baseline source so
        // the app returns REAL articles out of the box with zero configuration;
        // any keyed APIs below are merged on top when configured. Disable in the
        // test suite via NEWSPAPERFLOW_GOOGLE_NEWS=false so tests stay network-free.
        'google_news' => [
            'enabled'  => (bool) env('NEWSPAPERFLOW_GOOGLE_NEWS', true),
            'endpoint' => 'https://news.google.com/rss/search',
        ],
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
        'hacker_news' => env('NEWSPAPERFLOW_SIGNAL_HN', true),
        // Bluesky firehose — free, commercial-clean secondary signal (count
        // how many posts link a candidate URL). Off until implemented.
        'bluesky'     => env('NEWSPAPERFLOW_SIGNAL_BLUESKY', false),
        // Reddit — intentionally OFF: 2025+ terms require a paid licence for
        // commercial use. Do not enable without a licensed Reddit API app.
        'reddit'      => env('NEWSPAPERFLOW_SIGNAL_REDDIT', false),
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
        'model'   => env('NEWSPAPERFLOW_LLM_MODEL', 'claude-haiku-4-5-20251001'),
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

    /*
    |--------------------------------------------------------------------------
    | Local-area news
    |--------------------------------------------------------------------------
    */

    'areas' => [
        // How long after creating an area a Free user may still edit/delete it
        // (typo-grace). After this window a Free user's single area is locked;
        // only upgrading to Pro re-enables changes. Pro is never locked.
        'edit_grace_hours' => 24,

        // Zippopotam.us — keyless US ZIP → city/state geocoder. Best-effort:
        // used only to enrich an area when a ZIP is supplied; failures fall
        // back to the city/state the user typed.
        'zip_geocoder' => env('NEWSPAPERFLOW_ZIP_GEOCODER', 'https://api.zippopotam.us'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI local-source discovery
    |--------------------------------------------------------------------------
    |
    | A self-learning extension of config/localnews.php. When a user creates an
    | area whose location isn't in the curated directory, a web-search-grounded
    | Claude call discovers its real local outlets and caches them (shared
    | across all users). Degrades gracefully: with no ANTHROPIC_API_KEY, or
    | when disabled, areas simply use the curated directory + statewide/country
    | fallback exactly as before.
    |
    */

    'discovery' => [
        'enabled' => (bool) env('NEWSPAPERFLOW_DISCOVERY', true) && (bool) env('ANTHROPIC_API_KEY'),

        // Web-search + reasoning benefit from a stronger model than the Haiku
        // used for one-line summaries. One call per location, cached forever,
        // so cost is negligible.
        'model'         => env('NEWSPAPERFLOW_DISCOVERY_MODEL', 'claude-sonnet-5'),
        'max_searches'  => (int) env('NEWSPAPERFLOW_DISCOVERY_MAX_SEARCHES', 5),
        'max_domains'   => (int) env('NEWSPAPERFLOW_DISCOVERY_MAX_DOMAINS', 6),

        // Learned records re-verify after this many days (outlets rebrand/merge).
        'reverify_days' => (int) env('NEWSPAPERFLOW_DISCOVERY_REVERIFY_DAYS', 120),

        // Best-effort HTTP liveness check that also canonicalizes redirects
        // (auto-catches rebrands like ktuu.com → alaskasnewssource.com).
        'validate_liveness' => (bool) env('NEWSPAPERFLOW_DISCOVERY_VALIDATE', true),

        // Anthropic server-side web search tool identifier.
        'web_search_tool' => env('NEWSPAPERFLOW_DISCOVERY_TOOL', 'web_search_20250305'),
    ],
];
