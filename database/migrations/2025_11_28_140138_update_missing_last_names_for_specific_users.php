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

        $updates = [
            33380 => 'Killean',
            33414 => 'Bloomfield',
            33295 => 'Shawer',
            33268 => 'Finlayson',
            33225 => 'Macrae',
        ];

        foreach ($updates as $userId => $lastName) {
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'last_name'  => $lastName,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Optional rollback: set these last_name values back to NULL
        $userIds = [33380, 33414, 33295, 33268, 33225];

        DB::table('users')
            ->whereIn('id', $userIds)
            ->update([
                'last_name'  => null,
                'updated_at' => now(),
            ]);
    }
};
