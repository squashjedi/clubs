<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members') || ! Schema::hasTable('players')) {
            return;
        }

        DB::table('members')
            ->whereNull('user_id')
            ->whereNull('player_id')
            ->orderBy('id')
            ->chunkById(200, function ($members) {
                $now = Carbon::now();

                foreach ($members as $member) {
                    $playerId = DB::table('players')->insertGetId([
                        'first_name'       => $member->first_name,
                        'last_name'       => $member->last_name,
                        'email'      => null,
                        'gender'     => 'unknown',
                        'tel_no'      => null,
                        'dob'        => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('members')
                        ->where('id', $member->id)
                        ->update([
                            'player_id' => $playerId,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        DB::table('members')
            ->whereNull('user_id')
            ->update([
                'player_id' => null,
            ]);
    }
};
