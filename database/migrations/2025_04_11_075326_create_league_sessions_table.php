<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('league_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id');
            $table->datetime('starting_at');
            $table->datetime('ending_at');
            $table->datetime('published_at')->nullable();
            $table->datetime('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('league_sessions');
    }
};
