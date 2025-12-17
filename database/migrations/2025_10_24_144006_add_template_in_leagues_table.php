<?php
// database/migrations/2025_10_25_060500_add_template_json_to_leagues.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'leagues';
    private const COL   = 'template';
    private const AFTER = 'sport_id';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: '.self::TABLE);
        }

        $afterSql = Schema::hasColumn(self::TABLE, self::AFTER)
            ? ' AFTER `'.self::AFTER.'`'
            : '';

        if (!Schema::hasColumn(self::TABLE, self::COL)) {
            // Add column with NOT NULL DEFAULT (json_array())
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD COLUMN `%s` JSON NOT NULL DEFAULT (json_array())%s',
                self::TABLE, self::COL, $afterSql
            ));
        } else {
            // Ensure type/default/placement
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` JSON NOT NULL DEFAULT (json_array())%s',
                self::TABLE, self::COL, $afterSql
            ));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return;
        }
        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP COLUMN `%s`',
            self::TABLE, self::COL
        ));
    }
};
