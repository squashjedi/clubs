<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Ensure there are no NULL player_id values
        $nullCount = DB::table('entrants')->whereNull('player_id')->count();

        if ($nullCount > 0) {
            throw new RuntimeException(
                "Cannot make entrants.player_id NOT NULL – {$nullCount} records still have NULL values."
            );
        }

        Schema::table('entrants', function (Blueprint $table) {
            // 2) Drop the existing FK that uses ON DELETE SET NULL
            // Name from your error: entrants_player_id_foreign
            $table->dropForeign('entrants_player_id_foreign');

            // 3) Make the column NOT NULL
            $table->unsignedBigInteger('player_id')->nullable(false)->change();

            // 4) Re-add FK without SET NULL
            $table->foreign('player_id')
                ->references('id')
                ->on('players')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // or ->cascadeOnDelete() if you prefer
        });
    }

    public function down(): void
    {
        Schema::table('entrants', function (Blueprint $table) {
            // Drop the RESTRICT FK
            $table->dropForeign(['player_id']);

            // Make column nullable again
            $table->unsignedBigInteger('player_id')->nullable()->change();

            // Recreate FK with SET NULL (original behaviour)
            $table->foreign('player_id')
                ->references('id')
                ->on('players')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }
};
