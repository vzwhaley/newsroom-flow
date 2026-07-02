<?php

use App\Http\Controllers\AdsTxtController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigestUnsubscribeController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedArticleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\WorldNewsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public / marketing
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('/pricing', fn () => Inertia::render('Pricing'))->name('pricing');

// Live, clickable "World News" demo feed — what a real topic feed looks like.
Route::get('/world-news', [WorldNewsController::class, 'show'])->name('world-news');

Route::get('/how-to-use', fn () => Inertia::render('HowToUse'))->name('how-to-use');
Route::get('/faq', fn () => Inertia::render('Faq'))->name('faq');
Route::get('/about', fn () => Inertia::render('About'))->name('about');

Route::get('/privacy', fn () => Inertia::render('Legal/Privacy'))->name('privacy');
Route::get('/terms', fn () => Inertia::render('Legal/Terms'))->name('terms');

// Authorized-seller declarations, built from configured publisher IDs.
// ads.txt = AdSense (this site); app-ads.txt = AdMob (the mobile apps).
Route::get('/ads.txt', [AdsTxtController::class, 'ads']);
Route::get('/app-ads.txt', [AdsTxtController::class, 'appAds']);

// SEO sitemap of the public pages.
Route::get('/sitemap.xml', function () {
    $paths = ['/', '/pricing', '/world-news', '/how-to-use', '/faq', '/about', '/privacy', '/terms'];

    $urls = collect($paths)
        ->map(fn ($p) => '  <url><loc>'.e(url($p)).'</loc></url>')
        ->implode("\n");

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
        .$urls."\n"
        .'</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// One-click digest unsubscribe from the daily email (signed URL — no login).
// GET serves the human clicking the footer link; POST serves RFC 8058
// List-Unsubscribe-Post requests sent automatically by mailbox providers.
Route::get('/digest/unsubscribe/{user}', DigestUnsubscribeController::class)
    ->middleware(['signed', 'throttle:12,1'])
    ->name('digest.unsubscribe');
Route::post('/digest/unsubscribe/{user}', DigestUnsubscribeController::class)
    ->middleware(['signed', 'throttle:12,1'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Authenticated app
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Topics (store/refresh throttled — refresh fans out to the news API)
    Route::post('/topics', [TopicController::class, 'store'])->middleware('throttle:30,1')->name('topics.store');
    Route::post('/topics/reorder', [TopicController::class, 'reorder'])->name('topics.reorder');
    Route::post('/topics/{topic}/refresh', [TopicController::class, 'refresh'])->middleware('throttle:15,1')->name('topics.refresh');
    Route::patch('/topics/{topic}/mutes', [TopicController::class, 'mutes'])->name('topics.mutes');
    Route::post('/topics/{topic}/read-all', [TopicController::class, 'markAllRead'])->name('topics.read-all');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');

    // Per-article actions (XHR/JSON; summary throttled — it calls the LLM)
    Route::post('/articles/{article}/summary', [ArticleController::class, 'summary'])->middleware('throttle:30,1')->name('articles.summary');
    Route::post('/articles/{article}/read', [ArticleController::class, 'markRead'])->name('articles.read');
    Route::delete('/articles/{article}/read', [ArticleController::class, 'markUnread'])->name('articles.unread');

    // Search across feeds + saved (Pro)
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // Archive of rotated-out articles (Pro)
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive');

    // Saved ("read later") articles — Pro
    Route::get('/saved', [SavedArticleController::class, 'index'])->name('saved.index');
    Route::post('/saved', [SavedArticleController::class, 'store'])->name('saved.store');
    Route::delete('/saved/{saved}', [SavedArticleController::class, 'destroy'])->name('saved.destroy');
});

Route::middleware('auth')->group(function () {
    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // News preferences (refresh hour + timezone)
    Route::patch('/preferences', [PreferencesController::class, 'update'])->name('preferences.update');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/lifetime', [BillingController::class, 'lifetime'])->name('billing.lifetime');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
});

require __DIR__.'/auth.php';
