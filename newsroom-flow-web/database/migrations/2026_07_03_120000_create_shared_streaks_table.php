<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Public brag-card links (/streak/{code}) — snapshot of the streak at
        // share time so the card stays truthful after the streak changes.
        Schema::create('shared_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12)->unique();
            $table->string('name');
            $table->unsignedInteger('streak');
            $table->unsignedInteger('total_reads')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_streaks');
    }
};
