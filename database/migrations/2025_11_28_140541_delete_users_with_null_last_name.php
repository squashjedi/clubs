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

        // Find all users where last_name is NULL
        $userIds = DB::table('users')
            ->whereNull('last_name')
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            return;
        }

        // Delete users in chunks to avoid performance issues
        foreach (array_chunk($userIds, 200) as $chunk) {
            DB::table('users')->whereIn('id', $chunk)->delete();
        }
    }

    public function down(): void
    {
        // Irreversible — you cannot restore deleted users
        // Leaving this intentionally empty.
    }
};
