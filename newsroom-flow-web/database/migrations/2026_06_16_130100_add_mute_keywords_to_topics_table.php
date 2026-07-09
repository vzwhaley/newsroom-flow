<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Keywords to mute for this topic — articles whose headline or
            // description contains any of these are filtered out. Pro feature.
            $table->json('mute_keywords')->nullable()->after('query');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn('mute_keywords');
        });
    }
};
