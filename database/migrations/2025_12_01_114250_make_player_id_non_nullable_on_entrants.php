<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Safety check: ensure no NULLs remain
        $nullCount = DB::table('entrants')->whereNull('player_id')->count();

        if ($nullCount > 0) {
            throw new RuntimeException(
                "Cannot make entrants.player_id NOT NULL – {$nullCount} records still have NULL values."
            );
        }

        Schema::table('entrants', function (Blueprint $table) {
            $table->unsignedBigInteger('player_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('entrants', function (Blueprint $table) {
            $table->unsignedBigInteger('player_id')->nullable()->change();
        });
    }
};
