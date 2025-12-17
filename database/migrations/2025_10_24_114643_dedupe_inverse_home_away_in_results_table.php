<?php
// database/migrations/2025_10_25_020000_dedupe_inverse_home_away_in_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('results')) {
            throw new \RuntimeException('Table results does not exist.');
        }

        // Why: keep a single canonical row per unordered pair by id-stability.
        DB::statement(
            'DELETE r1 FROM `results` r1
               INNER JOIN `results` r2
                   ON r1.`home_contestant_id` = r2.`away_contestant_id`
                  AND r1.`away_contestant_id` = r2.`home_contestant_id`
                  AND r1.`id` > r2.`id`'
        );
    }

    public function down(): void
    {
        // Irreversible: deleted rows cannot be restored here.
    }
};
