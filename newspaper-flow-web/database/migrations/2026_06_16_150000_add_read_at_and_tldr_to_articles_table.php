<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // When the owning user marked this article read (null = unread).
            $table->timestamp('read_at')->nullable()->after('fetched_at');

            // Cached on-demand "TL;DR this" summary (Pro). Generated once, then
            // reused until the article rotates out of the feed.
            $table->text('tldr')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['read_at', 'tldr']);
        });
    }
};
