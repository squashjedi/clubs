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
            ! Schema::hasTable('players') ||
            ! Schema::hasTable('player_user') ||
            ! Schema::hasTable('members')
        ) {
            return;
        }

        $now = Carbon::now();

        // 1. Create the new player: Huey Lamb
        $playerId = DB::table('players')->insertGetId([
            'first_name'       => 'Huey',
            'last_name'       => 'Lamb',
            'email'      => 'leonsky44@googlemail.com',
            'gender'     => 'unknown', // adjust if your players table has no gender
            'tel_no'      => '07485784414',
            'dob'        => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Create the player_user link for user_id = 350 with relationship guardian
        DB::table('player_user')->insert([
            'player_id'  => $playerId,
            'user_id'    => 199,
            'relationship' => 'guardian',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 3. Update members.id = 896 with the new player_id
        DB::table('members')
            ->where('id', 896)
            ->update([
                'player_id'  => $playerId,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('players') ||
            ! Schema::hasTable('player_user') ||
            ! Schema::hasTable('members')
        ) {
            return;
        }

        // Remove the member link first
        DB::table('members')
            ->where('id', 896)
            ->update([
                'player_id' => null,
            ]);

        // Remove the player_user link
        DB::table('player_user')
            ->where('user_id', 350)
            ->where('relationship', 'guardian')
            ->delete();

        // Remove the player record
        DB::table('players')
            ->where('first_name', 'Huey')
            ->where('last_name', 'Lamb')
            ->where('tel_no', '07485784414')
            ->delete();
    }
};
