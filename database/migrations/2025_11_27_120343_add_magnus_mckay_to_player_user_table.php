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
            'first_name'       => 'Magnus',
            'last_name'       => 'McKay',
            'email'      => 'tmckayuk@gmail.com',
            'gender'     => 'unknown', // adjust if your players table has no gender
            'tel_no'      => '07305241176',
            'dob'        => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Create the player_user link for user_id = 350 with relationship guardian
        DB::table('player_user')->insert([
            'player_id'  => $playerId,
            'user_id'    => 33340,
            'relationship'       => 'guardian',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 3. Update members.id = 896 with the new player_id
        DB::table('members')
            ->where('id', 844)
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
            ->where('id', 844)
            ->update([
                'player_id' => null,
            ]);

        // Remove the player_user link
        DB::table('player_user')
            ->where('user_id', 33340)
            ->where('relationship', 'guardian')
            ->delete();

        // Remove the player record
        DB::table('players')
            ->where('first_name', 'Magnus')
            ->where('last_name', 'McKay')
            ->where('tel_no', '07305241176')
            ->delete();
    }
};
