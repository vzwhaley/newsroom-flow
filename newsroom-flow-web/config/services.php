<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Social sign-in (Socialite). Each provider's button only renders when
    | its CLIENT_ID is configured (see HandleInertiaRequests::share).
    */

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'apple' => [
        'client_id'     => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect'      => env('APPLE_REDIRECT_URI', '/auth/apple/callback'),
    ],

    'discord' => [
        'client_id'     => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect'      => env('DISCORD_REDIRECT_URI', '/auth/discord/callback'),
    ],

    /*
    | Push notifications. Each platform activates only when configured; until
    | then a NullPushSender is used so the rest of the system works offline.
    |
    | FCM (Android): point FCM_CREDENTIALS at the service-account JSON file
    | downloaded from the Firebase console; FCM_PROJECT_ID is its project_id.
    | APNs (iOS): create an APNs Auth Key (.p8) in the Apple Developer portal;
    | set APNS_KEY_ID, APNS_TEAM_ID, APNS_BUNDLE_ID, and APNS_KEY_PATH (the .p8).
    */

    'fcm' => [
        'project_id'  => env('FCM_PROJECT_ID'),
        'credentials' => env('FCM_CREDENTIALS'), // path to service-account JSON
    ],

    'apns' => [
        'key_id'     => env('APNS_KEY_ID'),
        'team_id'    => env('APNS_TEAM_ID'),
        'bundle_id'  => env('APNS_BUNDLE_ID', 'com.newsroomflow.ios'),
        'key_path'   => env('APNS_KEY_PATH'),    // path to AuthKey_XXXX.p8
        'production' => env('APNS_PRODUCTION', false),
    ],

];
