<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();

            $table->string('headline');
            $table->text('description');
            $table->text('url');
            $table->string('source')->nullable();      // Publisher name
            $table->text('image_url')->nullable();

            // Stable fingerprint (hash of canonical URL) used to dedupe
            // across refreshes so we never show the same story twice.
            $table->string('fingerprint')->index();

            // Blended popularity score (engagement proxy, higher = hotter).
            $table->float('popularity_score')->default(0);

            // Ordering within the topic's 12-article feed (0 = top).
            $table->unsignedInteger('position')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at')->nullable();

            $table->timestamps();

            $table->unique(['topic_id', 'fingerprint']);
            $table->index(['topic_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
