<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Make sure tables/columns exist
        if (
            ! Schema::hasTable('contestants') ||
            ! Schema::hasTable('members') ||
            ! Schema::hasColumn('contestants', 'member_id') ||
            ! Schema::hasColumn('contestants', 'player_id') ||
            ! Schema::hasColumn('members', 'player_id')
        ) {
            return;
        }

        // Backfill contestants.player_id from members.player_id
        DB::table('contestants')
            ->whereNull('player_id')
            ->whereNotNull('member_id')
            ->orderBy('id')
            ->chunkById(200, function ($contestants) {
                foreach ($contestants as $contestant) {
                    // contestants.member_id -> members.id -> members.player_id
                    $playerId = DB::table('members')
                        ->where('id', $contestant->member_id)
                        ->value('player_id');

                    if ($playerId) {
                        DB::table('contestants')
                            ->where('id', $contestant->id)
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
        // Not safely reversible, so we leave this empty
    }
};
