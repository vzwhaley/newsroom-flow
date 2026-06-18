<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Include only articles new since the last digest (vs. the top set).
            $table->boolean('digest_new_only')->default(false)->after('digest_sent_at');
        });

        Schema::table('topics', function (Blueprint $table) {
            // Whether this topic is included in the daily email digest.
            $table->boolean('include_in_digest')->default(true)->after('mute_keywords');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('digest_new_only');
        });
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn('include_in_digest');
        });
    }
};
