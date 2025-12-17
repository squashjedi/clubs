<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add columns + FKs (nullable to start)
        Schema::table('results', function (Blueprint $table) {
            if (! Schema::hasColumn('results', 'home_player_id')) {
                $table->unsignedBigInteger('home_player_id')
                    ->nullable()
                    ->after('home_contestant_id');

                $table->foreign('home_player_id')
                    ->references('id')
                    ->on('players')
                    ->cascadeOnUpdate()
                    ->nullOnDelete(); // temporary while nullable
            }

            if (! Schema::hasColumn('results', 'away_player_id')) {
                $table->unsignedBigInteger('away_player_id')
                    ->nullable()
                    ->after('away_contestant_id');

                $table->foreign('away_player_id')
                    ->references('id')
                    ->on('players')
                    ->cascadeOnUpdate()
                    ->nullOnDelete(); // temporary while nullable
            }
        });

        // 2) Backfill from contestants -> players
        if (
            ! Schema::hasTable('contestants') ||
            ! Schema::hasColumn('contestants', 'player_id') ||
            ! Schema::hasColumn('results', 'home_contestant_id') ||
            ! Schema::hasColumn('results', 'away_contestant_id')
        ) {
            return;
        }

        DB::table('results')
            ->where(function ($q) {
                $q->whereNull('home_player_id')
                  ->orWhereNull('away_player_id');
            })
            ->orderBy('id')
            ->chunkById(200, function ($results) {
                foreach ($results as $result) {
                    $updates = [];

                    // Home player
                    if (is_null($result->home_player_id) && ! is_null($result->home_contestant_id)) {
                        $homePlayerId = DB::table('contestants')
                            ->where('id', $result->home_contestant_id)
                            ->value('player_id');

                        if ($homePlayerId) {
                            $updates['home_player_id'] = $homePlayerId;
                        }
                    }

                    // Away player
                    if (is_null($result->away_player_id) && ! is_null($result->away_contestant_id)) {
                        $awayPlayerId = DB::table('contestants')
                            ->where('id', $result->away_contestant_id)
                            ->value('player_id');

                        if ($awayPlayerId) {
                            $updates['away_player_id'] = $awayPlayerId;
                        }
                    }

                    if (! empty($updates)) {
                        $updates['updated_at'] = now();

                        DB::table('results')
                            ->where('id', $result->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            if (Schema::hasColumn('results', 'home_player_id')) {
                $table->dropForeign(['home_player_id']);
                $table->dropColumn('home_player_id');
            }

            if (Schema::hasColumn('results', 'away_player_id')) {
                $table->dropForeign(['away_player_id']);
                $table->dropColumn('away_player_id');
            }
        });
    }
};
