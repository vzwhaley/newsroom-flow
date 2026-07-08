<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Lifetime Pro (one-time purchase). Set by HandleLifetimeCheckout.
            $table->timestamp('lifetime_purchased_at')->nullable()->after('remember_token');
            // Audit hint when a Lifetime purchase is refunded (drops to Free).
            $table->timestamp('lifetime_refunded_at')->nullable()->after('lifetime_purchased_at');

            // Default refresh hour (0-23) in the user's timezone.
            $table->unsignedTinyInteger('refresh_hour')->default(6)->after('lifetime_refunded_at');
            $table->string('timezone')->default('UTC')->after('refresh_hour');

            // Social sign-in provider IDs (Google / Apple / Discord).
            $table->string('google_id')->nullable()->unique()->after('timezone');
            $table->string('apple_id')->nullable()->unique()->after('google_id');
            $table->string('discord_id')->nullable()->unique()->after('apple_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'lifetime_purchased_at',
                'lifetime_refunded_at',
                'refresh_hour',
                'timezone',
                'google_id',
                'apple_id',
                'discord_id',
            ]);
        });
    }
};
