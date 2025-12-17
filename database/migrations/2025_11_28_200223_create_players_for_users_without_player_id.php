<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure required tables exist
        if (
            ! Schema::hasTable('users') ||
            ! Schema::hasTable('players') ||
            ! Schema::hasTable('player_user')
        ) {
            return;
        }

        // Detect column availability
        $hasUserFirstName = Schema::hasColumn('users', 'first_name');
        $hasUserLastName  = Schema::hasColumn('users', 'last_name');
        $hasUserEmail     = Schema::hasColumn('users', 'email');
        $hasUserGender    = Schema::hasColumn('users', 'gender');
        $hasUserDob       = Schema::hasColumn('users', 'dob');
        $hasUserTel       = Schema::hasColumn('users', 'tel_no');

        $hasPlayerEmail   = Schema::hasColumn('players', 'email');
        $hasPlayerGender  = Schema::hasColumn('players', 'gender');
        $hasPlayerDob     = Schema::hasColumn('players', 'dob');
        $hasPlayerTel     = Schema::hasColumn('players', 'tel_no');

        $pivotHasRelationship = Schema::hasColumn('player_user', 'relationship');

        // Process users with NULL player_id in chunks
        DB::table('users')
            ->whereNull('player_id')
            ->orderBy('id')
            ->chunkById(300, function ($users) use (
                $hasUserFirstName, $hasUserLastName, $hasUserEmail,
                $hasUserGender, $hasUserDob, $hasUserTel,
                $hasPlayerEmail, $hasPlayerGender, $hasPlayerDob, $hasPlayerTel,
                $pivotHasRelationship
            ) {
                $now = Carbon::now();

                foreach ($users as $user) {

                    // First & last names for players
                    $firstName = $hasUserFirstName ? ($user->first_name ?? null) : null;
                    $lastName  = $hasUserLastName  ? ($user->last_name ?? null)  : null;

                    // Fallback if both are empty
                    if ((!$firstName || $firstName === '') && (!$lastName || $lastName === '')) {
                        if ($hasUserEmail && !empty($user->email)) {
                            [$local] = explode('@', $user->email);
                            $firstName = $local;
                        }
                    }

                    // Build player record
                    $playerData = [
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if ($hasPlayerEmail && $hasUserEmail) {
                        $playerData['email'] = $user->email;
                    }

                    if ($hasPlayerGender) {
                        $playerData['gender'] = $hasUserGender
                            ? ($user->gender ?? 'unknown')
                            : 'unknown';
                    }

                    if ($hasPlayerDob && $hasUserDob) {
                        $playerData['dob'] = $user->dob;
                    }

                    if ($hasPlayerTel && $hasUserTel) {
                        $playerData['tel_no'] = $user->tel_no;
                    }

                    // Create the player
                    $playerId = DB::table('players')->insertGetId($playerData);

                    // Insert pivot entry (user ↔ player)
                    $pivotExists = DB::table('player_user')
                        ->where('user_id', $user->id)
                        ->where('player_id', $playerId)
                        ->exists();

                    if (! $pivotExists) {
                        $pivotData = [
                            'user_id'    => $user->id,
                            'player_id'  => $playerId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        if ($pivotHasRelationship) {
                            $pivotData['relationship'] = 'self';
                        }

                        DB::table('player_user')->insert($pivotData);
                    }
                }
            });
    }

    public function down(): void
    {
        // Not safe to reverse automatically — players created by this migration
        // cannot be distinguished from pre-existing players.
    }
};
