<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedArticleController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public / marketing
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('/pricing', fn () => Inertia::render('Pricing'))->name('pricing');
Route::get('/how-to-use', fn () => Inertia::render('HowToUse'))->name('how-to-use');
Route::get('/faq', fn () => Inertia::render('Faq'))->name('faq');
Route::get('/about', fn () => Inertia::render('About'))->name('about');

Route::get('/privacy', fn () => Inertia::render('Legal/Privacy'))->name('privacy');
Route::get('/terms', fn () => Inertia::render('Legal/Terms'))->name('terms');

/*
|--------------------------------------------------------------------------
| Authenticated app
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Topics
    Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
    Route::post('/topics/reorder', [TopicController::class, 'reorder'])->name('topics.reorder');
    Route::post('/topics/{topic}/refresh', [TopicController::class, 'refresh'])->name('topics.refresh');
    Route::patch('/topics/{topic}/mutes', [TopicController::class, 'mutes'])->name('topics.mutes');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');

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
