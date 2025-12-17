<?php

use Illuminate\Support\Facades\DB;
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
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('club_league_id');
            $table->foreignId('club_id')->constrained();
            $table->foreignId('sport_id')->constrained();
            $table->json('template')->default(DB::raw('(JSON_ARRAY())'));
            $table->foreignId('tally_unit_id')->constrained();
            $table->tinyInteger('best_of');
            $table->string('name');
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
