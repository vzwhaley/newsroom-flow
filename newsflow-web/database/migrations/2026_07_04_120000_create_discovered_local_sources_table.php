<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A self-learning local-outlet directory. When a user creates an area whose
 * location isn't in the curated config/localnews.php, an AI discovery job
 * (web-search-grounded) finds its real local outlets and caches them here,
 * keyed by location and shared across ALL users — so each place is discovered
 * at most once, then reused instantly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_local_sources', function (Blueprint $table) {
            $table->id();
            $table->string('location_key')->unique(); // "us|tn|greeneville"
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->json('domains');                   // ["greenevillesun.com", ...]
            $table->json('outlets')->nullable();       // {"greenevillesun.com":"The Greeneville Sun"}
            $table->string('source', 16)->default('ai'); // 'ai' | 'manual'
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_local_sources');
    }
};
