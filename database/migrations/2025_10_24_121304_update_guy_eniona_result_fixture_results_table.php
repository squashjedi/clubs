<?php
// database/migrations/2025_10_25_032500_update_fixture_from_result_155_no_limit.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            ['results', ['id', 'fixture_contestant_id']],
            ['fixture_contestants', ['id', 'fixture_id']],
            ['fixtures', ['id', 'is_admin', 'user_id']],
        ] as [$table, $cols]) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException("Missing table: {$table}");
            }
            foreach ($cols as $col) {
                if (!Schema::hasColumn($table, $col)) {
                    throw new \RuntimeException("Missing column `{$col}` on `{$table}`.");
                }
            }
        }

        // Why: scalar subquery avoids UPDATE ... LIMIT restriction.
        DB::statement(
            "UPDATE `fixtures` f
                SET f.`is_admin` = 0,
                    f.`user_id`  = 67
              WHERE f.`id` = (
                    SELECT fc.`fixture_id`
                      FROM `fixture_contestants` fc
                      JOIN `results` r ON r.`fixture_contestant_id` = fc.`id`
                     WHERE r.`id` = 155
                     LIMIT 1
              )"
        );
    }

    public function down(): void
    {
        // Irreversible without prior snapshot of fixtures fields.
    }
};
