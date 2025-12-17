<?php
// database/migrations/2025_10_25_060000_move_club_id_after_club_league_id_in_leagues.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE      = 'leagues';
    private const COL        = 'club_id';
    private const AFTER_COL  = 'club_league_id';
    private const DOWN_AFTER = 'id'; // adjust to your original layout if needed

    public function up(): void
    {
        $this->assertColumnsExist(self::TABLE, [self::COL, self::AFTER_COL]);

        $definition = $this->currentColumnDefinition(self::TABLE, self::COL);
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s AFTER `%s`',
            self::TABLE, self::COL, $definition, self::AFTER_COL
        ));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return;
        }

        // If DOWN_AFTER doesn't exist anymore, drop the AFTER clause.
        $definition = $this->currentColumnDefinition(self::TABLE, self::COL);
        if (Schema::hasColumn(self::TABLE, self::DOWN_AFTER)) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` %s AFTER `%s`',
                self::TABLE, self::COL, $definition, self::DOWN_AFTER
            ));
        } else {
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` %s',
                self::TABLE, self::COL, $definition
            ));
        }
    }

    /** Build a MySQL column definition preserving type, nullability, default, extra, and comment. */
    private function currentColumnDefinition(string $table, string $column): string
    {
        $db  = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
              LIMIT 1',
            [$db, $table, $column]
        );
        if (!$row) {
            throw new \RuntimeException("Column {$table}.{$column} not found.");
        }

        $parts = [];
        $parts[] = $row->COLUMN_TYPE;                               // e.g., "bigint(20) unsigned"
        $parts[] = $row->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';

        if ($row->COLUMN_DEFAULT !== null) {
            $def = $row->COLUMN_DEFAULT;
            $parts[] = 'DEFAULT ' . (is_numeric($def) ? $def : DB::getPdo()->quote($def));
        } elseif ($row->IS_NULLABLE === 'YES') {
            $parts[] = 'DEFAULT NULL';
        }

        if (!empty($row->EXTRA) && stripos($row->EXTRA, 'on update') !== false) {
            $parts[] = trim($row->EXTRA);
        }

        if (!empty($row->COLUMN_COMMENT)) {
            $parts[] = 'COMMENT ' . DB::getPdo()->quote($row->COLUMN_COMMENT);
        }

        return implode(' ', $parts);
    }

    private function assertColumnsExist(string $table, array $cols): void
    {
        if (!Schema::hasTable($table)) {
            throw new \RuntimeException("Missing table: {$table}");
        }
        foreach ($cols as $c) {
            if (!Schema::hasColumn($table, $c)) {
                throw new \RuntimeException("Missing column `{$c}` on `{$table}`.");
            }
        }
    }
};
