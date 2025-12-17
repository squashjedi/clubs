<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Safety: ensure no NULLs remain
        $nullCount = DB::table('results')
            ->whereNull('home_player_id')
            ->orWhereNull('away_player_id')
            ->count();

        if ($nullCount > 0) {
            throw new RuntimeException(
                "Cannot make home_player_id / away_player_id NOT NULL – {$nullCount} rows still have NULL values."
            );
        }

        Schema::table('results', function (Blueprint $table) {
            // 2) Drop existing FKs (with SET NULL)
            $table->dropForeign(['home_player_id']);
            $table->dropForeign(['away_player_id']);

            // 3) Make columns NOT NULL
            $table->unsignedBigInteger('home_player_id')->nullable(false)->change();
            $table->unsignedBigInteger('away_player_id')->nullable(false)->change();

            // 4) Re-add FKs with RESTRICT (or CASCADE if you prefer)
            $table->foreign('home_player_id')
                ->references('id')
                ->on('players')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // or ->cascadeOnDelete()

            $table->foreign('away_player_id')
                ->references('id')
                ->on('players')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // or ->cascadeOnDelete()
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // Drop RESTRICT fks
            $table->dropForeign(['home_player_id']);
            $table->dropForeign(['away_player_id']);

            // Make columns nullable again
            $table->unsignedBigInteger('home_player_id')->nullable()->change();
            $table->unsignedBigInteger('away_player_id')->nullable()->change();

            // Restore original SET NULL behaviour
            $table->foreign('home_player_id')
                ->references('id')
                ->on('players')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('away_player_id')
                ->references('id')
                ->on('players')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }
};
