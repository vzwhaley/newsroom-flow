<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Immediate watchlist push (Pro) — separate from the daily push so
            // users can keep the morning digest push but silence keyword hits
            // (or vice versa).
            $table->boolean('watchlist_push_enabled')->default(true)->after('push_sent_at');

            // Cached AI daily briefing (Pro) — one per user per local day.
            $table->text('briefing')->nullable()->after('watchlist_push_enabled');
            $table->date('briefing_for')->nullable()->after('briefing');
        });

        // One row per user per local day with at least one article read.
        // Survives article rotation, powering reading streaks + totals.
        Schema::create('reading_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('reads')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        // Branded share links (/s/{code}) — a snapshot of the article at share
        // time so the card keeps rendering after the story rotates out.
        Schema::create('shared_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12)->unique();
            $table->string('headline', 500);
            $table->text('description')->nullable();
            $table->string('url', 2048);
            $table->string('source')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('topic_name')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_articles');
        Schema::dropIfExists('reading_days');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['watchlist_push_enabled', 'briefing', 'briefing_for']);
        });
    }
};
