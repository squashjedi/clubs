<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add nullable foreign key to players table
            $table->foreignId('player_id')
                ->nullable()
                ->after('id')              // adjust position if needed
                ->constrained('players')   // references players(id)
                ->nullOnDelete();          // set null if player is deleted
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->dropColumn('player_id');
        });
    }
};