<?php
// database/migrations/2025_10_25_030000_update_match_at_for_result_155_from_fixture_contestants.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('results')) {
            throw new \RuntimeException('Missing table: results');
        }
        foreach (['match_at', 'fixture_contestant_id'] as $col) {
            if (!Schema::hasColumn('results', $col)) {
                throw new \RuntimeException("Missing column `{$col}` on `results`.");
            }
        }
        if (!Schema::hasTable('fixture_contestants')) {
            throw new \RuntimeException('Missing table: fixture_contestants');
        }

        // Copy created_at from fixture_contestants -> results.match_at for the specific row.
        DB::statement(
            "UPDATE `results` r
                SET r.`match_at` = (
                    SELECT fc.`created_at`
                      FROM `fixture_contestants` fc
                     WHERE fc.`id` = r.`fixture_contestant_id`
                     LIMIT 1
                )
              WHERE r.`id` = 155"
        );
    }

    public function down(): void
    {
        // Irreversible without prior snapshot of results.match_at.
    }
};
