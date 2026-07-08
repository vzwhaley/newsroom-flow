<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google AdSense — website ads (Free tier + public marketing pages)
    |--------------------------------------------------------------------------
    |
    | The NewsroomFlow website uses AdSense (the mobile apps use AdMob — see
    | config/admob.php). The publisher client + slot IDs are public, so they're
    | injected into the SPA via Inertia shared props (HandleInertiaRequests).
    |
    | Eligibility:
    |   - Public / marketing pages (Home, Pricing, World News demo, How to Use,
    |     FAQ, About) show ads to EVERY non-Pro visitor — including anonymous,
    |     logged-out prospects — since that's the bulk of the site's traffic.
    |   - The signed-in app pages (Dashboard, Saved, Search, Archive) show ads
    |     to Free users only.
    |   - Pro NEVER receives any client or slot IDs, so a Pro user literally
    |     cannot load an ad on any surface.
    |
    | Leave any value blank to keep that slot off. With ADSENSE_CLIENT blank
    | (typical local dev), AdSlot.vue renders a dashed "Advertisement"
    | placeholder so layout review still works without serving a real ad.
    |
    | IMPORTANT — before setting ADSENSE_CLIENT in production you MUST have a
    | Google-certified Consent Management Platform (CMP) live for EEA/UK/CH
    | traffic (Google requires this to serve ads there). Either keep the
    | first-party CookieConsent banner (US-only launches) or enable Google's
    | certified CMP and set ADSENSE_USE_GOOGLE_CMP=true.
    |
    | Mirrors the pattern from the sibling apps (FileFlow / My Emergency Screen).
    */

    'client' => env('ADSENSE_CLIENT'), // e.g. ca-pub-0000000000000000

    'use_google_cmp' => env('ADSENSE_USE_GOOGLE_CMP', false),

    'slots' => [
        // One slot per (page × placement) so each ad reports separately in
        // AdSense. Create a Display ad unit per entry and set its 10-digit
        // slot ID via the matching env var (you can point several env vars at
        // one unit if you don't need that granularity).

        // Site-wide marketing slot rendered above the footer on every public
        // page (PublicLayout).
        'marketing'        => env('ADSENSE_SLOT_MARKETING'),

        // Home (/)
        'home_top'         => env('ADSENSE_SLOT_HOME_TOP'),
        'home_mid'         => env('ADSENSE_SLOT_HOME_MID'),

        // Pricing (/pricing)
        'pricing_top'      => env('ADSENSE_SLOT_PRICING_TOP'),

        // World News demo (/world-news)
        'world_news_top'   => env('ADSENSE_SLOT_WORLD_NEWS_TOP'),

        // How to Use (/how-to-use)
        'how_to_use_top'   => env('ADSENSE_SLOT_HOW_TO_USE_TOP'),

        // FAQ (/faq)
        'faq_top'          => env('ADSENSE_SLOT_FAQ_TOP'),

        // About (/about)
        'about_top'        => env('ADSENSE_SLOT_ABOUT_TOP'),

        // Dashboard (/dashboard, Free only) — top + bottom
        'dashboard_top'    => env('ADSENSE_SLOT_DASHBOARD_TOP'),
        'dashboard_bottom' => env('ADSENSE_SLOT_DASHBOARD_BOTTOM'),

        // Saved (/saved, Free only)
        'saved_top'        => env('ADSENSE_SLOT_SAVED_TOP'),

        // Search (/search, Free only)
        'search_top'       => env('ADSENSE_SLOT_SEARCH_TOP'),

        // Archive (/archive, Free only)
        'archive_top'      => env('ADSENSE_SLOT_ARCHIVE_TOP'),
    ],

];
