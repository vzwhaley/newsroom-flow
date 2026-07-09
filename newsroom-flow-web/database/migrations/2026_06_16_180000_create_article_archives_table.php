<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Snapshot of an article that rotated out of a feed (Pro history).
            // We don't FK to topics — a topic may later be deleted.
            $table->string('topic_name');
            $table->string('headline');
            $table->text('description')->nullable();
            $table->text('url');
            $table->string('source')->nullable();
            $table->text('image_url')->nullable();
            $table->string('fingerprint');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at');

            $table->timestamps();

            // One copy of a given story per user.
            $table->unique(['user_id', 'fingerprint']);
            $table->index(['user_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_archives');
    }
};
