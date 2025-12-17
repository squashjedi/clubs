<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('users') ||
            ! Schema::hasTable('players') ||
            ! Schema::hasColumn('users', 'player_id') ||
            ! Schema::hasColumn('users', 'last_name') ||
            ! Schema::hasColumn('players', 'last_name')
        ) {
            return;
        }

        DB::table('users')
            ->whereNull('last_name')
            ->whereNotNull('player_id')
            ->orderBy('id')
            ->chunkById(300, function ($users) {
                $playerIds = $users->pluck('player_id')->filter()->unique()->all();

                if (empty($playerIds)) {
                    return;
                }

                // Get players' last names keyed by id
                $players = DB::table('players')
                    ->whereIn('id', $playerIds)
                    ->pluck('last_name', 'id');

                $now = Carbon::now();

                foreach ($users as $user) {
                    if (! $user->player_id) {
                        continue;
                    }

                    $playerLastName = $players[$user->player_id] ?? null;

                    if (! $playerLastName) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'last_name'  => $playerLastName,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Conservative rollback: clear last_name only for users that still have it NULL originally is hard to know.
        // We’ll just null last_name again for users that have a player_id and non-null last_name.
        if (
            ! Schema::hasTable('users') ||
            ! Schema::hasColumn('users', 'player_id') ||
            ! Schema::hasColumn('users', 'last_name')
        ) {
            return;
        }

        DB::table('users')
            ->whereNotNull('player_id')
            ->whereNotNull('last_name')
            ->update([
                'last_name' => null,
            ]);
    }
};
