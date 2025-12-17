<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_player', function (Blueprint $table) {
            $table->renameColumn('club_member_id', 'club_player_id');
        });
    }

    public function down(): void
    {
        Schema::table('club_player', function (Blueprint $table) {
            $table->renameColumn('club_player_id', 'club_member_id');
        });
    }
};
