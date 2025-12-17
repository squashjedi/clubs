<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // Drop existing FK
            $table->dropForeign(['division_id']);

            // Recreate with CASCADE
            $table->foreign('division_id')
                ->references('id')->on('divisions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['division_id']);

            // Original behavior: RESTRICT on delete
            $table->foreign('division_id')
                ->references('id')->on('divisions')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
