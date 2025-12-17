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
        Schema::create('sport_tally_unit', function (Blueprint $table) {
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tally_unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('max_best_of');
            $table->primary(['sport_id','tally_unit_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_tally_unit');
    }
};