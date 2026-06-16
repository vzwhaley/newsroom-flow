<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Optional parent topic — lets a user group subtopics under a
            // category, e.g. "Information Technology" → "OpenAI", "Anthropic".
            // One level of nesting only (a child cannot itself be a parent).
            // Deleting a parent removes its children too.
            $table->foreignId('parent_id')
                ->nullable()
                ->after('user_id')
                ->constrained('topics')
                ->cascadeOnDelete();

            $table->index(['user_id', 'parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
