<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'last_name')) {
            return;
        }

        DB::table('users')
            ->where('id', 248)
            ->update([
                'last_name'  => 'Kennedy',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Optional rollback: set last_name back to NULL
        DB::table('users')
            ->where('id', 248)
            ->update([
                'last_name'  => null,
                'updated_at' => now(),
            ]);
    }
};
