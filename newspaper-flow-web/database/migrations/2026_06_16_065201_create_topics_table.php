<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // What the user typed, e.g. "World News" or "Indiana Jones".
            $table->string('name');
            // Normalised query used when scouring sources.
            $table->string('query')->nullable();
            // Ordering within the user's personal "newspaper".
            $table->unsignedInteger('position')->default(0);

            // When the daily refresh last ran for this topic, and whether
            // the last run actually found brand-new stories.
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamp('last_new_articles_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
