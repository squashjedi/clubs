<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_player', function (Blueprint $table) {

            // Drop the useless copied indexes
            $table->dropIndex('members_club_id_foreign');
            $table->dropIndex('members_player_id_foreign');

            // Make player_id NOT NULL
            $table->unsignedBigInteger('player_id')->nullable(false)->change();

            // Add actual foreign keys
            $table->foreign('club_id', 'club_player_club_id_foreign')
                ->references('id')->on('clubs')
                ->onDelete('cascade');

            $table->foreign('player_id', 'club_player_player_id_foreign')
                ->references('id')->on('players')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('club_player', function (Blueprint $table) {

            // Drop real foreign keys
            $table->dropForeign('club_player_club_id_foreign');
            $table->dropForeign('club_player_player_id_foreign');

            // Make player_id nullable again
            $table->unsignedBigInteger('player_id')->nullable()->change();

            // Recreate indexes if needed
            $table->index('club_id', 'members_club_id_foreign');
            $table->index('player_id', 'members_player_id_foreign');
        });
    }
};
