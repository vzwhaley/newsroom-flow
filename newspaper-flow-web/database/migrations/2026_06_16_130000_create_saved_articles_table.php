<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Snapshot of the article at save time. We don't FK to `articles`
            // because the daily refresh rotates old articles out — a saved
            // ("read later") item must survive that.
            $table->string('headline');
            $table->text('description')->nullable();
            $table->text('url');
            $table->string('source')->nullable();
            $table->text('image_url')->nullable();
            $table->string('topic_name')->nullable(); // which topic it came from

            // Stable per-user fingerprint so the same story can't be saved twice.
            $table->string('fingerprint');

            $table->timestamps();

            $table->unique(['user_id', 'fingerprint']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_articles');
    }
};
