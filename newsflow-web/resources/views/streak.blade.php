<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $card->streak }}-Day Reading Streak — NewsroomFlow™</title>

        <meta name="robots" content="noindex">
        <meta name="description" content="{{ $card->name }} is on a {{ $card->streak }}-day reading streak on NewsroomFlow — build your own newsroom.">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="NewsroomFlow™">
        <meta property="og:title" content="🔥 {{ $card->streak }}-day reading streak on NewsroomFlow">
        <meta property="og:description" content="{{ $card->name }} has read the news {{ $card->streak }} days in a row ({{ number_format($card->total_reads) }} articles all-time). Build your own newsroom — free for 2 topics.">
        <meta property="og:url" content="{{ url('/streak/'.$card->code) }}">
        <meta property="og:image" content="https://newsflow.app/img/og-default.png">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="🔥 {{ $card->streak }}-day reading streak on NewsroomFlow">
        <meta name="twitter:description" content="{{ $card->name }} has read the news {{ $card->streak }} days in a row. Build your own newsroom.">
        <meta name="twitter:image" content="https://newsflow.app/img/og-default.png">

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=source-serif-4:400,600,700&display=swap" rel="stylesheet" />

        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Figtree', system-ui, sans-serif;
                background: linear-gradient(135deg, #fff7ed 0%, #eff6ff 100%);
                color: #111827;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                text-align: center;
            }
            .card {
                background: #fff;
                border-radius: 1.5rem;
                box-shadow: 0 20px 50px rgba(234, 88, 12, .18);
                max-width: 26rem;
                width: 100%;
                overflow: hidden;
            }
            .accent { height: .375rem; background: linear-gradient(90deg, #f97316, #ef4444, #8b5cf6); }
            .inner { padding: 2.5rem 2rem; }
            .flame { font-size: 3.5rem; line-height: 1; }
            .num {
                font-family: 'Source Serif 4', Georgia, serif;
                font-size: 3.25rem;
                font-weight: 700;
                margin-top: .5rem;
            }
            .label { font-size: 1.05rem; color: #4b5563; margin-top: .25rem; }
            .who { font-size: .9rem; color: #9ca3af; margin-top: 1rem; }
            .total { font-size: .85rem; color: #9ca3af; }
            .btn {
                display: inline-block;
                margin-top: 1.5rem;
                background: linear-gradient(90deg, #2563eb, #4f46e5);
                color: #fff;
                font-weight: 600;
                font-size: .95rem;
                text-decoration: none;
                border-radius: 9999px;
                padding: .75rem 1.75rem;
            }
            .btn:hover { filter: brightness(1.08); }
            .btn:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }
            .cta { margin-top: 1.5rem; font-size: .9rem; color: #6b7280; max-width: 26rem; }
        </style>
    </head>
    <body>
        <main class="card">
            <div class="accent"></div>
            <div class="inner">
                <div class="flame" aria-hidden="true">🔥</div>
                <div class="num">{{ $card->streak }}-Day</div>
                <p class="label">reading streak on NewsroomFlow™</p>
                <p class="who">{{ $card->name }} · {{ number_format($card->total_reads) }} articles read all-time</p>
                <a class="btn" href="{{ url('/') }}">Start Your Own Streak</a>
            </div>
        </main>
        <p class="cta">
            NewsroomFlow is your own newsroom — follow only the topics you care about,
            with the day's most popular stories every morning. Free for 2 topics.
        </p>
    </body>
</html>
