<?php

use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BriefingController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\PreferencesController;
use App\Http\Controllers\Api\SavedController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TopicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JSON API for the native apps (Android / iOS) — Sanctum bearer tokens
|--------------------------------------------------------------------------
*/

// Public auth endpoints (rate-limited to deter brute force).
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:3,1');

    Route::get('/me', [FeedController::class, 'me']);
    Route::get('/config', [ConfigController::class, 'show']);
    Route::get('/feed', [FeedController::class, 'index']);
    Route::get('/search', [SearchController::class, 'index']);
    Route::get('/archive', [ArchiveController::class, 'index']);
    Route::put('/preferences', [PreferencesController::class, 'update']);

    // Push notification device tokens (APNs / FCM)
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    // Topics (store/refresh throttled — refresh fans out to the news API)
    Route::post('/topics', [TopicController::class, 'store'])->middleware('throttle:30,1');
    Route::post('/topics/reorder', [TopicController::class, 'reorder']);
    Route::post('/topics/{topic}/refresh', [TopicController::class, 'refresh'])->middleware('throttle:15,1');
    Route::patch('/topics/{topic}/mutes', [TopicController::class, 'mutes']);
    Route::patch('/topics/{topic}/digest', [TopicController::class, 'digest']);
    Route::post('/topics/{topic}/read-all', [TopicController::class, 'markAllRead']);
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy']);

    // Article actions (summary throttled — it calls the LLM)
    Route::post('/articles/{article}/read', [ArticleController::class, 'markRead']);
    Route::delete('/articles/{article}/read', [ArticleController::class, 'markUnread']);
    Route::post('/articles/{article}/summary', [ArticleController::class, 'summary'])->middleware('throttle:30,1');
    Route::post('/articles/{article}/share', [ShareController::class, 'store'])->middleware('throttle:30,1');

    // AI daily briefing (Pro; cached per user per local day)
    Route::get('/briefing', [BriefingController::class, 'show'])->middleware('throttle:12,1');

    // Reading stats + streak brag card (all tiers)
    Route::get('/stats', [StatsController::class, 'show']);
    Route::post('/stats/share', [StatsController::class, 'share'])->middleware('throttle:12,1');

    // Saved
    Route::get('/saved', [SavedController::class, 'index']);
    Route::post('/saved', [SavedController::class, 'store']);
    Route::delete('/saved/{saved}', [SavedController::class, 'destroy']);
});
