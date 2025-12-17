<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const OLD   = 'f';
    private const NEW   = 'home_score';
    private const AFTER = 'away_contestant_id';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: ' . self::TABLE);
        }
        if (!Schema::hasColumn(self::TABLE, self::AFTER)) {
            throw new \RuntimeException(sprintf('Missing column `%s` on `%s`.', self::AFTER, self::TABLE));
        }

        // The column might already be renamed; detect source name.
        $src = Schema::hasColumn(self::TABLE, self::OLD) ? self::OLD
             : (Schema::hasColumn(self::TABLE, self::NEW) ? self::NEW : null);
        if ($src === null) {
            throw new \RuntimeException(sprintf('Neither `%s` nor `%s` exists on `%s`.', self::OLD, self::NEW, self::TABLE));
        }

        // Preserve NULL/NOT NULL, DEFAULT, and COMMENT from current column.
        $meta = $this->columnMeta(self::TABLE, $src);
        $nullable = $meta['is_nullable'] ? 'NULL' : 'NOT NULL';
        $defaultSql = $meta['default_sql'];  // already formatted or empty
        $commentSql = $meta['comment_sql'];  // already formatted or empty

        // Rename + change type + reposition.
        DB::statement(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` SMALLINT UNSIGNED %s %s %s AFTER `%s`',
            self::TABLE,
            $src,
            self::NEW,
            $nullable,
            $defaultSql,
            $commentSql,
            self::AFTER
        ));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }
        if (!Schema::hasColumn(self::TABLE, self::NEW)) {
            // If it was never renamed, nothing to revert.
            return;
        }

        // Preserve NULL/NOT NULL, DEFAULT, and COMMENT while reverting to TINYINT (signed).
        $meta = $this->columnMeta(self::TABLE, self::NEW);
        $nullable = $meta['is_nullable'] ? 'NULL' : 'NOT NULL';
        $defaultSql = $meta['default_sql'];
        $commentSql = $meta['comment_sql'];

        DB::statement(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` TINYINT %s %s %s',
            self::TABLE,
            self::NEW,
            self::OLD,
            $nullable,
            $defaultSql,
            $commentSql
        ));
    }

    /**
     * Fetch column nullability/default/comment and return preformatted SQL snippets.
     */
    private function columnMeta(string $table, string $column): array
    {
        $db = DB::getDatabaseName();
        $r = DB::selectOne(
            'SELECT IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
              LIMIT 1',
            [$db, $table, $column]
        );
        if (!$r) {
            throw new \RuntimeException("Column {$table}.{$column} not found.");
        }

        $isNullable = ($r->IS_NULLABLE === 'YES');

        // DEFAULT formatting: numeric unquoted; others quoted; NULL explicit if nullable and no default.
        $defaultSql = '';
        if ($r->COLUMN_DEFAULT !== null) {
            $def = $r->COLUMN_DEFAULT;
            $defaultSql = 'DEFAULT ' . (is_numeric($def) ? $def : DB::getPdo()->quote($def));
        } elseif ($isNullable) {
            $defaultSql = 'DEFAULT NULL';
        }

        $commentSql = '';
        if (!empty($r->COLUMN_COMMENT)) {
            $commentSql = 'COMMENT ' . DB::getPdo()->quote($r->COLUMN_COMMENT);
        }

        return [
            'is_nullable' => $isNullable,
            'default_sql' => $defaultSql,
            'comment_sql' => $commentSql,
        ];
    }
};
