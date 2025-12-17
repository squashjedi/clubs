<?php
// database/migrations/2025_10_25_070000_make_tiers_index_signed.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'tiers';
    private const COL   = 'index';

    public function up(): void
    {
        $this->assertColumn();
        $def = $this->columnDef(self::TABLE, self::COL, makeUnsigned: false); // strip UNSIGNED
        DB::statement(sprintf('ALTER TABLE `%s` MODIFY `%s` %s', self::TABLE, self::COL, $def));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return;
        }
        $def = $this->columnDef(self::TABLE, self::COL, makeUnsigned: true); // force UNSIGNED
        DB::statement(sprintf('ALTER TABLE `%s` MODIFY `%s` %s', self::TABLE, self::COL, $def));
    }

    private function assertColumn(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            throw new \RuntimeException(sprintf('Missing %s.%s', self::TABLE, self::COL));
        }
    }

    /**
     * Build a MySQL column definition preserving width/null/default/extra/comment.
     * When $makeUnsigned is true, ensure UNSIGNED; when false, remove UNSIGNED.
     */
    private function columnDef(string $table, string $column, bool $makeUnsigned): string
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

        // Start with reported type, toggle UNSIGNED bit.
        $type = (string) $r->COLUMN_TYPE;             // e.g. "int(11) unsigned" or "int(11)"
        $type = preg_replace('/\s+unsigned\b/i', '', $type) ?: $type; // strip if present
        if ($makeUnsigned && stripos($type, 'unsigned') === false) {
            $type .= ' UNSIGNED';
        }

        $parts = [];
        $parts[] = $type;
        $parts[] = ($r->IS_NULLABLE === 'YES') ? 'NULL' : 'NOT NULL';

        if ($r->COLUMN_DEFAULT !== null) {
            $def = $r->COLUMN_DEFAULT;
            $parts[] = 'DEFAULT ' . (is_numeric($def) ? $def : DB::getPdo()->quote($def));
        } elseif ($r->IS_NULLABLE === 'YES') {
            $parts[] = 'DEFAULT NULL';
        }

        // Preserve "ON UPDATE ..." etc. if present in EXTRA
        if (!empty($r->EXTRA) && stripos($r->EXTRA, 'on update') !== false) {
            $parts[] = trim($r->EXTRA);
        }

        if (!empty($r->COLUMN_COMMENT)) {
            $parts[] = 'COMMENT ' . DB::getPdo()->quote($r->COLUMN_COMMENT);
        }

        return implode(' ', $parts);
    }
};
