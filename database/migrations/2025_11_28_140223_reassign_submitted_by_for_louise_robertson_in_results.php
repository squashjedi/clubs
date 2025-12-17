<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('results') || ! Schema::hasColumn('results', 'submitted_by')) {
            return;
        }

        DB::table('results')
            ->where('submitted_by', 33149)
            ->update([
                'submitted_by' => 33255,
                'updated_at'   => now(),
            ]);
    }

    public function down(): void
    {
        // Rollback optional: revert to old value
        DB::table('results')
            ->where('submitted_by', 33255)
            ->update([
                'submitted_by' => 33149,
                'updated_at'   => now(),
            ]);
    }
};
