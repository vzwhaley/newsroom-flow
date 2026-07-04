<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe Price IDs for NewsFlow Pro
    |--------------------------------------------------------------------------
    |
    | Set these in your .env file. Each Price ID corresponds to a price
    | created in the Stripe dashboard under Products → NewsFlow Pro.
    |
    |   Monthly  Pro  — $4.99/mo   (recurring)
    |   Yearly   Pro  — $49.99/yr  (recurring)
    |   Lifetime Pro  — $149.99    (one-time, current major version)
    |
    */

    'prices' => [
        'monthly'  => env('STRIPE_PRICE_PRO_MONTHLY'),
        'annual'   => env('STRIPE_PRICE_PRO_ANNUAL'),
        'lifetime' => env('STRIPE_PRICE_PRO_LIFETIME'),
    ],

    /*
    | Human-readable display prices. These are ONLY for the marketing /
    | pricing UI — Stripe is always the source of truth for the actual
    | charge. Keep them in sync with the Stripe dashboard.
    */
    'display_prices' => [
        'monthly'  => '4.99',
        'annual'   => '49.99',
        'lifetime' => '149.99',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Type
    |--------------------------------------------------------------------------
    |
    | Cashier groups subscriptions under a "type" name. We use "default".
    |
    */

    'subscription_type' => 'default',

    /*
    |--------------------------------------------------------------------------
    | What the Free tier allows (single source of truth)
    |--------------------------------------------------------------------------
    |
    | Free users may follow up to 2 topics. Pro (any paid tier) is
    | unlimited. These limits are referenced by App\Models\User helper
    | methods so there is one place to tune them.
    |
    */

    'free_limits' => [
        'topics' => 2,

        // Local-area news feeds a Free user may follow. Free gets a single
        // area which is permanent after a short typo-grace window (see
        // config('newsflow.areas.edit_grace_hours')); Pro is unlimited.
        'areas' => 1,
    ],

    /*
    | Number of articles we keep per topic. The daily refresh tries to
    | replace all of them with fresh stories, but always guarantees the
    | user has this many to read.
    */
    'articles_per_topic' => 12,
];
