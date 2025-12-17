<?php
// database/migrations/2025_10_25_061500_update_sport_9_name_to_pool.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sports') || !Schema::hasColumn('sports', 'name') || !Schema::hasColumn('sports', 'id')) {
            throw new \RuntimeException('Missing sports table or required columns.');
        }

        DB::table('sports')->where('id', 9)->update(['name' => 'Pool']);
    }

    public function down(): void
    {
        // Irreversible: unknown previous value.
        // If you know it, replace with:
        // DB::table('sports')->where('id', 9)->update(['name' => 'PreviousName']);
    }
};
