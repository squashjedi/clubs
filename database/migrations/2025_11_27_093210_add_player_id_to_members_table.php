<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Add nullable FK to players table
            $table->foreignId('player_id')
                ->nullable()
                ->after('user_id')       // adjust position as needed
                ->constrained('players') // references players(id)
                ->nullOnDelete();        // sets player_id = null if player is deleted
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->dropColumn('player_id');
        });
    }
};

