<?php
// database/migrations/2025_10_24_230000_drop_sport_id_from_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const COL = 'sport_id';
    private const REF_TABLE = 'sports';
    private const REF_COL = 'id';
    private const INDEX_NAME = 'results_sport_id_index';       // used in down()
    private const FK_NAME = 'results_sport_id_foreign';        // used in down()

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, self::COL)) {
            return; // already removed
        }

        // Drop FK by discovered name to avoid 1091.
        if ($fkName = $this->getForeignKeyName(self::TABLE, self::COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        } else {
            // Fallback: safe no-op if none.
            Schema::table(self::TABLE, function (Blueprint $table) {
                try { $table->dropForeign([self::COL]); } catch (\Throwable $e) {}
            });
        }

        // Drop any non-primary indexes on sport_id.
        foreach ($this->getIndexesOnColumn(self::TABLE, self::COL) as $idx) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($idx) {
                try { $table->dropIndex($idx); } catch (\Throwable $e) {}
            });
        }

        // Drop the column.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn(self::COL);
        });
    }

    public function down(): void
    {

    }

    /** Return FK constraint name for table.column, or null if none. */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME
               FROM information_schema.KEY_COLUMN_USAGE
              WHERE CONSTRAINT_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1',
            [$db, $table, $column]
        );
        return $row->CONSTRAINT_NAME ?? null;
    }

    /** @return string[] index names on the given column (excluding PRIMARY). */
    private function getIndexesOnColumn(string $table, string $column): array
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT DISTINCT INDEX_NAME
               FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND INDEX_NAME <> "PRIMARY"',
            [$db, $table, $column]
        );
        return array_map(static fn($r) => $r->INDEX_NAME, $rows);
    }
};
