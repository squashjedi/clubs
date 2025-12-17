<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrants', function (Blueprint $table) {
            if (!Schema::hasColumn('entrants', 'player_id')) {
                $table->unsignedBigInteger('player_id')
                    ->nullable()
                    ->after('member_id');

                $table->foreign('player_id')
                    ->references('id')
                    ->on('players')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });

        // Backfill player_id from members table
        DB::table('entrants')
            ->whereNull('player_id')
            ->whereNotNull('member_id')
            ->orderBy('id')
            ->chunkById(200, function ($entrants) {
                foreach ($entrants as $entrant) {
                    $playerId = DB::table('members')
                        ->where('id', $entrant->member_id)
                        ->value('player_id');

                    if ($playerId) {
                        DB::table('entrants')
                            ->where('id', $entrant->id)
                            ->update([
                                'player_id'  => $playerId,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('entrants', function (Blueprint $table) {
            if (Schema::hasColumn('entrants', 'player_id')) {
                $table->dropForeign(['player_id']);
                $table->dropColumn('player_id');
            }
        });
    }
};
