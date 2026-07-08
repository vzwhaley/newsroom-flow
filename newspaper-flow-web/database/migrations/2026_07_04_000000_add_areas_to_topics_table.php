<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Local-area news reuses the topics pipeline: an "area" is a topic with
 * kind='area' plus structured location fields and a geocoded search query.
 * This gives areas the daily refresh, feed, archive, and TL;DR machinery for
 * free while keeping them a separate surface from the user's chosen topics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->string('kind', 16)->default('topic')->after('parent_id')->index();
            $table->string('locality')->nullable()->after('query');       // city
            $table->string('region')->nullable()->after('locality');      // state / province
            $table->string('postal_code', 16)->nullable()->after('region'); // zip
            $table->string('country_code', 2)->nullable()->after('postal_code'); // ISO-3166 alpha-2
        });

        // Existing rows are all topics.
        DB::table('topics')->whereNull('kind')->update(['kind' => 'topic']);
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn(['kind', 'locality', 'region', 'postal_code', 'country_code']);
        });
    }
};
