<?php
// database/migrations/2025_10_25_000000_move_league_id_after_club_id_in_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const COL   = 'league_id';
    private const AFTER = 'club_id';
    private const DOWN_AFTER = 'match_at'; // adjust if your original position differs

    public function up(): void
    {
        $this->assertColumnsExist([self::AFTER, self::COL]);

        $definition = $this->currentColumnDefinition(self::TABLE, self::COL);
        $sql = sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s AFTER `%s`',
            self::TABLE, self::COL, $definition, self::AFTER
        );
        DB::statement($sql);
    }

    public function down(): void
    {
        if (!Schema::hasColumn(self::TABLE, self::COL)) {
            return;
        }
        // If DOWN_AFTER missing, MySQL will ignore AFTER or fail. Guard it.
        if (!Schema::hasColumn(self::TABLE, self::DOWN_AFTER)) {
            // Fallback: move to end without AFTER clause
            $definition = $this->currentColumnDefinition(self::TABLE, self::COL);
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` %s',
                self::TABLE, self::COL, $definition
            ));
            return;
        }

        $definition = $this->currentColumnDefinition(self::TABLE, self::COL);
        $sql = sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s AFTER `%s`',
            self::TABLE, self::COL, $definition, self::DOWN_AFTER
        );
        DB::statement($sql);
    }

    /**
     * Build a MySQL column definition string for an existing column that preserves
     * type, unsigned, nullability, default, and comment. Keeps FKs intact.
     */
    private function currentColumnDefinition(string $table, string $column): string
    {
        $db = DB::getDatabaseName();
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
        $parts[] = $row->COLUMN_TYPE;                           // e.g. "bigint(20) unsigned"
        $parts[] = $row->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';

        // Preserve DEFAULT if explicitly set (including 0, empty string).
        if ($row->COLUMN_DEFAULT !== null) {
            $default = $row->COLUMN_DEFAULT;
            // Numeric defaults: no quotes; others quoted.
            $needsQuote = !is_numeric($default);
            $parts[] = 'DEFAULT ' . ($needsQuote ? DB::getPdo()->quote($default) : $default);
        } elseif ($row->IS_NULLABLE === 'YES') {
            // MySQL may implicitly default NULL; explicit is fine.
            $parts[] = 'DEFAULT NULL';
        }

        // Preserve "ON UPDATE CURRENT_TIMESTAMP" etc. if present in EXTRA.
        if (!empty($row->EXTRA)) {
            // EXTRA may include multiple flags; pass through only the "on update ..." part.
            $extra = trim($row->EXTRA);
            if (stripos($extra, 'on update') !== false) {
                $parts[] = $extra;
            }
        }

        if (!empty($row->COLUMN_COMMENT)) {
            $parts[] = 'COMMENT ' . DB::getPdo()->quote($row->COLUMN_COMMENT);
        }

        return implode(' ', $parts);
    }

    /** Ensure required columns exist before attempting reorder. */
    private function assertColumnsExist(array $cols): void
    {
        foreach ($cols as $c) {
            if (!Schema::hasColumn(self::TABLE, $c)) {
                throw new \RuntimeException(sprintf('Missing column `%s` on `%s`.', $c, self::TABLE));
            }
        }
    }
};
