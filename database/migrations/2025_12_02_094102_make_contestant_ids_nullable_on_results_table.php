<?php
// database/migrations/2025_12_02_000000_make_contestant_ids_nullable_on_results_table.php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // WHY: must drop constraints before changing nullability
            $table->dropForeign(['home_contestant_id']);
            $table->dropForeign(['away_contestant_id']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->unsignedBigInteger('home_contestant_id')->nullable()->change();
            $table->unsignedBigInteger('away_contestant_id')->nullable()->change();
        });

        Schema::table('results', function (Blueprint $table) {
            // Re-add with SET NULL to avoid dangling refs when a contestant is deleted
            $table->foreign('home_contestant_id')
                  ->references('id')->on('contestants')
                  ->nullOnDelete();

            $table->foreign('away_contestant_id')
                  ->references('id')->on('contestants')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['home_contestant_id']);
            $table->dropForeign(['away_contestant_id']);
        });

        Schema::table('results', function (Blueprint $table) {
            $table->unsignedBigInteger('home_contestant_id')->nullable(false)->change();
            $table->unsignedBigInteger('away_contestant_id')->nullable(false)->change();
        });

        Schema::table('results', function (Blueprint $table) {
            // Recreate your original behavior; adjust if it wasn't cascade
            $table->foreign('home_contestant_id')
                  ->references('id')->on('contestants')
                  ->cascadeOnDelete();

            $table->foreign('away_contestant_id')
                  ->references('id')->on('contestants')
                  ->cascadeOnDelete();
        });
    }
};
