<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Opt-in daily "your NewsroomFlow is ready" email digest.
            $table->boolean('digest_enabled')->default(false)->after('timezone');
            // Guards against double-sends within the same refresh window.
            $table->timestamp('digest_sent_at')->nullable()->after('digest_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['digest_enabled', 'digest_sent_at']);
        });
    }
};
