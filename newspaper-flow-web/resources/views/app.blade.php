<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Google AdSense loader — site verification + ad serving. Rendered
             from config('adsense.client') (ADSENSE_CLIENT), the same publisher
             ID that powers /ads.txt and every <ins> ad unit. Pro users still
             load this (it verifies the site) but receive no slot IDs, so no ad
             ever renders for them. --}}
        @if (config('adsense.client'))
            <script async
                    src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('adsense.client') }}"
                    crossorigin="anonymous"></script>
        @endif

        <title inertia>NewsroomFlow™ — Your own customized news topics, every day</title>

        {{-- Server-rendered SEO defaults. Because the app is Inertia (no SSR),
             these are what JS-less social scrapers (iMessage/Slack/Facebook/
             LinkedIn) read for link previews. Per-page <SeoHead> enhances title,
             canonical, OG and JSON-LD for crawlers that execute JS (Google). --}}
        <meta name="description" content="Build your own newsroom. Follow only the topics you care about and get the day's most popular headlines on each, every morning — free for 2 topics.">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="NewsroomFlow™">
        <meta property="og:title" content="NewsroomFlow™ — Your own customized news topics, every day">
        <meta property="og:description" content="Follow only the topics you care about and get the day's most popular headlines on each, every morning.">
        <meta property="og:url" content="https://newspaperflow.app{{ request()->getPathInfo() }}">
        <meta property="og:image" content="https://newspaperflow.app/img/og-default.png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="NewsroomFlow™ — Your own customized news topics, every day">
        <meta name="twitter:description" content="Follow only the topics you care about and get the day's most popular headlines on each, every morning.">
        <meta name="twitter:image" content="https://newspaperflow.app/img/og-default.png">
        <meta name="theme-color" content="#2563eb">

        {{-- Defense-in-depth: robots.txt already disallows private routes, but
             belt-and-suspenders noindex any page that isn't a public marketing
             page (dashboard, auth, billing, profile, search, saved, archive…). --}}
        @php
            $publicPaths = ['/', '/pricing', '/world-news', '/how-to-use', '/faq', '/about', '/privacy', '/terms'];
        @endphp
        @unless (in_array(request()->getPathInfo(), $publicPaths, true))
            <meta name="robots" content="noindex">
        @endunless

        <!-- Favicons — the logo's newspaper mark on a brand-blue tile -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=source-serif-4:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
