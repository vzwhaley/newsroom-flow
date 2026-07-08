<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $share->headline }} — NewsroomFlow™</title>

        {{-- Thin/duplicative content: keep share cards out of the index, but
             give every scraper rich Open Graph tags for the link preview. --}}
        <meta name="robots" content="noindex">
        <meta name="description" content="{{ $share->description ?: 'Shared via NewsroomFlow — build your own newsroom.' }}">

        <meta property="og:type" content="article">
        <meta property="og:site_name" content="NewsroomFlow™">
        <meta property="og:title" content="{{ $share->headline }}">
        <meta property="og:description" content="{{ $share->description ?: 'Shared via NewsroomFlow — build your own newsroom.' }}">
        <meta property="og:url" content="{{ url('/s/'.$share->code) }}">
        <meta property="og:image" content="{{ $share->image_url ?: 'https://newspaperflow.app/img/og-default.png' }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $share->headline }}">
        <meta name="twitter:description" content="{{ $share->description ?: 'Shared via NewsroomFlow — build your own newsroom.' }}">
        <meta name="twitter:image" content="{{ $share->image_url ?: 'https://newspaperflow.app/img/og-default.png' }}">

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=source-serif-4:400,600,700&display=swap" rel="stylesheet" />

        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Figtree', system-ui, sans-serif;
                background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 100%);
                color: #111827;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }
            .card {
                background: #fff;
                border-radius: 1.5rem;
                box-shadow: 0 20px 50px rgba(37, 99, 235, .15);
                max-width: 40rem;
                width: 100%;
                overflow: hidden;
            }
            .accent { height: .375rem; background: linear-gradient(90deg, #2563eb, #6366f1, #8b5cf6); }
            .inner { padding: 2rem; }
            .kicker {
                display: inline-block;
                background: #eff6ff;
                color: #1d4ed8;
                font-size: .75rem;
                font-weight: 600;
                letter-spacing: .04em;
                text-transform: uppercase;
                border-radius: 9999px;
                padding: .25rem .75rem;
                margin-bottom: 1rem;
            }
            h1 {
                font-family: 'Source Serif 4', Georgia, serif;
                font-size: 1.75rem;
                line-height: 1.25;
                margin-bottom: .75rem;
            }
            .desc { color: #4b5563; font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem; }
            .source { color: #9ca3af; font-size: .875rem; margin-bottom: 1.5rem; }
            .btn {
                display: inline-block;
                background: linear-gradient(90deg, #2563eb, #4f46e5);
                color: #fff;
                font-weight: 600;
                font-size: .95rem;
                text-decoration: none;
                border-radius: 9999px;
                padding: .75rem 1.75rem;
            }
            .btn:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }
            .cta {
                margin-top: 1.5rem;
                text-align: center;
                font-size: .9rem;
                color: #6b7280;
                max-width: 40rem;
            }
            .cta a { color: #1d4ed8; font-weight: 600; text-decoration: none; }
            .cta a:hover, .btn:hover { filter: brightness(1.08); }
            .brand { font-weight: 700; color: #111827; }
        </style>
    </head>
    <body>
        <main class="card">
            <div class="accent"></div>
            <div class="inner">
                @if ($share->topic_name)
                    <span class="kicker">{{ $share->topic_name }}</span>
                @endif
                <h1>{{ $share->headline }}</h1>
                @if ($share->description)
                    <p class="desc">{{ $share->description }}</p>
                @endif
                @if ($share->source)
                    <p class="source">{{ $share->source }}</p>
                @endif
                <a class="btn" href="{{ $share->url }}" rel="noopener noreferrer">Read the Full Story →</a>
            </div>
        </main>
        <p class="cta">
            Shared from <span class="brand">NewsroomFlow™</span> — build your own newsroom.
            Follow only the topics you care about, free for 2 topics.
            <a href="{{ url('/') }}">Try NewsroomFlow</a>
        </p>
    </body>
</html>
