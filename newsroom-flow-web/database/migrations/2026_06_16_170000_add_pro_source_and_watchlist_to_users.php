<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Publishers (name or domain) to exclude from every feed (Pro).
            $table->json('blocked_sources')->nullable()->after('digest_new_only');

            // Keywords to watch for across all feeds (Pro). Matching articles
            // are surfaced in a pinned "watchlist" section.
            $table->json('watch_keywords')->nullable()->after('blocked_sources');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['blocked_sources', 'watch_keywords']);
        });
    }
};
