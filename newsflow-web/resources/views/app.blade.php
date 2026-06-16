<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- SEO -->
        <meta name="description" content="NewsFlow builds you a personal newspaper. Follow only the topics you care about and get the day's most popular headlines on each — a more customizable Google News.">
        <meta property="og:title" content="NewsFlow — your news, only the topics you choose">
        <meta property="og:description" content="Build your own newsroom. Follow the topics you care about and get the day's most popular headlines on each, every morning.">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="NewsFlow">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="theme-color" content="#2563eb">

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
