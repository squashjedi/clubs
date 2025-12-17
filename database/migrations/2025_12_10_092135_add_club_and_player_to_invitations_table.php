<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // Add club_id after id
            $table->foreignId('club_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // Add player_id after club_id
            $table->foreignId('player_id')
                ->after('club_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // Must drop foreign keys before columns
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');

            $table->dropForeign(['player_id']);
            $table->dropColumn('player_id');
        });
    }
};
