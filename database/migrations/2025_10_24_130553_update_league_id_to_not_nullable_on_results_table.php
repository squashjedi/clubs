<?php
// database/migrations/2025_10_25_040000_make_league_id_not_null_on_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const COL   = 'league_id';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            throw new \RuntimeException(sprintf('Missing %s.%s', self::TABLE, self::COL));
        }

        // Guard: fail fast if any NULLs exist to avoid NOT NULL violation.
        $nulls = (int) DB::table(self::TABLE)->whereNull(self::COL)->count();
        if ($nulls > 0) {
            throw new \RuntimeException("Cannot enforce NOT NULL: {$nulls} rows have NULL ".self::TABLE.'.'.self::COL);
        }

        // Build definition preserving type/default/comment; swap to NOT NULL.
        $def = $this->columnDef(self::TABLE, self::COL, forceNotNull: true);
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s',
            self::TABLE, self::COL, $def
        ));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return;
        }
        // Revert to NULL-able (keep type/default/comment).
        $def = $this->columnDef(self::TABLE, self::COL, forceNull: true);
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s',
            self::TABLE, self::COL, $def
        ));
    }

    /**
     * Return a MySQL column definition string preserving type/default/comment.
     * Options:
     *  - forceNotNull: override to NOT NULL
     *  - forceNull:    override to NULL DEFAULT NULL if no explicit default set
     */
    private function columnDef(string $table, string $column, bool $forceNotNull = false, bool $forceNull = false): string
    {
        $db = DB::getDatabaseName();
        $r = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
              LIMIT 1',
            [$db, $table, $column]
        );
        if (!$r) {
            throw new \RuntimeException("Column {$table}.{$column} not found.");
        }

        $parts = [];
        $parts[] = $r->COLUMN_TYPE; // e.g., "bigint(20) unsigned"

        if ($forceNotNull) {
            $parts[] = 'NOT NULL';
        } elseif ($forceNull) {
            $parts[] = 'NULL';
        } else {
            $parts[] = ($r->IS_NULLABLE === 'YES') ? 'NULL' : 'NOT NULL';
        }

        // DEFAULT handling
        if ($r->COLUMN_DEFAULT !== null) {
            $def = $r->COLUMN_DEFAULT;
            $parts[] = 'DEFAULT ' . (is_numeric($def) ? $def : DB::getPdo()->quote($def));
        } elseif (!$forceNotNull && ($forceNull || $r->IS_NULLABLE === 'YES')) {
            // Only include DEFAULT NULL when column is nullable
            $parts[] = 'DEFAULT NULL';
        }

        // Preserve "ON UPDATE ..." if present in EXTRA
        if (!empty($r->EXTRA) && stripos($r->EXTRA, 'on update') !== false) {
            $parts[] = trim($r->EXTRA);
        }

        if (!empty($r->COLUMN_COMMENT)) {
            $parts[] = 'COMMENT ' . DB::getPdo()->quote($r->COLUMN_COMMENT);
        }

        return implode(' ', $parts);
    }
};
