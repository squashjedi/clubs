<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        //
        // 1) Create the pivot table with ENUM role
        //
        Schema::create('player_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('player_id')
                ->constrained('players')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Enum for relationship type
            $table->enum('relationship', [
                'self',        // the player themself
                'guardian',     // parent managing a child
            ])->default('self');

            $table->timestamps();

            // Ensure a user can't have duplicate links for the same player
            $table->unique(['player_id', 'user_id']);
        });

        //
        // 2) Backfill from users.player_id → pivot
        //
        if (! Schema::hasColumn('users', 'player_id')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('player_id')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                $now = now();

                foreach ($users as $user) {
                    DB::table('player_user')->insertOrIgnore([
                        'player_id'  => $user->player_id,
                        'user_id'    => $user->id,
                        'relationship'       => 'self',  // Safest assumption
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_user');
    }
};
