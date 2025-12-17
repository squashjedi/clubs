<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Safety: only run if tables exist
        if (
            ! Schema::hasTable('members') ||
            ! Schema::hasTable('users') ||
            ! Schema::hasTable('players')
        ) {
            return;
        }

        DB::table('members')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($members) {
                $now = Carbon::now();

                // get distinct user_ids in this chunk
                $userIds = $members->pluck('user_id')->filter()->unique()->all();

                // load those users and key by id
                $users = DB::table('users')
                    ->whereIn('id', $userIds)
                    ->get()
                    ->keyBy('id');

                foreach ($members as $member) {
                    if (! $member->user_id) {
                        continue;
                    }

                    /** @var \stdClass|null $user */
                    $user = $users->get($member->user_id);

                    if (! $user) {
                        continue; // no matching user, skip
                    }

                    $playerId = $user->player_id;

                    // If the user doesn't already have a player, create one
                    if (! $playerId) {
                        $playerId = DB::table('players')->insertGetId([
                            'first_name' => $member->first_name,
                            'last_name' => $member->last_name,
                            'email'      => $user->email ?? null,
                            'gender'     => 'unknown', // adjust or remove if your table differs
                            'tel_no'      => $user->tel_no ?? null,
                            'dob'        => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        // Link the user to this player
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'player_id'  => $playerId,
                                'updated_at' => $now,
                            ]);

                        // Update our in-memory copy so we don't create another player
                        $user->player_id = $playerId;
                        $users->put($user->id, $user);
                    }

                    // At this point $playerId definitely exists
                    DB::table('members')
                        ->where('id', $member->id)
                        ->update([
                            'player_id'  => $playerId,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Conservative rollback: just unlink player_id from members with user_id.
        // (We don't delete players because they may now be referenced elsewhere.)
        if (! Schema::hasTable('members')) {
            return;
        }

        DB::table('members')
            ->whereNotNull('user_id')
            ->update([
                'player_id' => null,
            ]);

        // If you also want to clear users.player_id on rollback, you can optionally:
        if (Schema::hasTable('users')) {
            DB::table('users')->update([
                'player_id' => null,
            ]);
        }
    }
};
