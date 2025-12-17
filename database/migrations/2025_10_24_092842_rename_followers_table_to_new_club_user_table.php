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
        Schema::rename('followers', 'club_user');
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::rename('club_user', 'followers');
    }
};
