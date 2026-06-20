<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Opt-in to the daily "your newsroom is ready" push notification.
            $table->boolean('push_enabled')->default(false)->after('digest_new_only');

            // Cutoff for "new since last push" so we don't re-announce stories.
            $table->timestamp('push_sent_at')->nullable()->after('push_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['push_enabled', 'push_sent_at']);
        });
    }
};
