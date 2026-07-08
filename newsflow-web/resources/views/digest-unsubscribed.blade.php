<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Unsubscribed — NewsroomFlow™</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        body { margin: 0; font-family: 'Figtree', system-ui, -apple-system, sans-serif; background: #f8fafc; color: #0f172a; display: grid; place-items: center; min-height: 100vh; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px 44px; max-width: 460px; text-align: center; box-shadow: 0 4px 16px rgba(15, 23, 42, .05); }
        h1 { font-size: 22px; margin: 0 0 10px; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; margin: 0 0 22px; }
        a.btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 11px 22px; border-radius: 999px; }
        .brand { font-weight: 700; margin-bottom: 18px; font-size: 18px; }
        .brand span { color: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">News<span>Flow</span></div>
        <h1>You're unsubscribed</h1>
        <p>{{ $user->email }} won't receive the daily digest anymore. Changed your mind? You can turn it back on any time from your account preferences.</p>
        <a class="btn" href="{{ route('dashboard') }}">Open NewsroomFlow</a>
    </div>
</body>
</html>
