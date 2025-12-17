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
        Schema::create('tally_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // "Sets", "Games", "Frames"
            $table->string('key')->unique();  // "sets", "games", "frames"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tally_units');
    }
};
