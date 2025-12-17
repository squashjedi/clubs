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
        Schema::dropIfExists('club_sport');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_sport', function (Blueprint $table) {
            //
        });
    }
};
