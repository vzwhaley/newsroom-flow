<?php

use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\PreferencesController;
use App\Http\Controllers\Api\SavedController;
use App\Http\Controllers\Api\SearchController;
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

    Route::get('/me', [FeedController::class, 'me']);
    Route::get('/feed', [FeedController::class, 'index']);
    Route::get('/search', [SearchController::class, 'index']);
    Route::get('/archive', [ArchiveController::class, 'index']);
    Route::put('/preferences', [PreferencesController::class, 'update']);

    // Topics
    Route::post('/topics', [TopicController::class, 'store']);
    Route::post('/topics/reorder', [TopicController::class, 'reorder']);
    Route::post('/topics/{topic}/refresh', [TopicController::class, 'refresh']);
    Route::patch('/topics/{topic}/mutes', [TopicController::class, 'mutes']);
    Route::patch('/topics/{topic}/digest', [TopicController::class, 'digest']);
    Route::post('/topics/{topic}/read-all', [TopicController::class, 'markAllRead']);
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy']);

    // Article actions
    Route::post('/articles/{article}/read', [ArticleController::class, 'markRead']);
    Route::delete('/articles/{article}/read', [ArticleController::class, 'markUnread']);
    Route::post('/articles/{article}/summary', [ArticleController::class, 'summary']);

    // Saved
    Route::get('/saved', [SavedController::class, 'index']);
    Route::post('/saved', [SavedController::class, 'store']);
    Route::delete('/saved/{saved}', [SavedController::class, 'destroy']);
});
