<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AdMob ad units — Free tier, in-app screens only (mobile)
    |--------------------------------------------------------------------------
    |
    | Surfaced to Free-tier clients via the /api/config endpoint so unit IDs are
    | never hardcoded in the mobile bundle. Pro tier never receives these — the
    | controller short-circuits to null.
    |
    | A single banner runs at the bottom of the Feed tab (the mobile equivalent
    | of the web Dashboard). It never appears inside a Pro feature.
    |
    | The native app *application* IDs (Android manifest / iOS
    | GADApplicationIdentifier) are build-time, not server-delivered.
    |
    | Mirrors the pattern from the sibling apps (FileFlow / My Emergency Screen).
    */

    'units' => [
        'feed_tab' => env('ADMOB_UNIT_FEED_TAB'),
    ],

    'app_id' => [
        'android' => env('ADMOB_APP_ID_ANDROID'),
        'ios' => env('ADMOB_APP_ID_IOS'),
    ],

];
