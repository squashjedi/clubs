<?php
// database/migrations/2025_10_25_041000_drop_fixture_contestant_id_from_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const COL = 'fixture_contestant_id';
    private const REF_TABLE = 'fixture_contestants';
    private const REF_COL = 'id';
    private const INDEX_NAME = 'results_fixture_contestant_id_index'; // used in down()
    private const FK_NAME = 'results_fixture_contestant_id_foreign';  // preferred name in down()

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return; // nothing to drop
        }

        // 1) Drop FK by discovered name (avoids 1091 errors).
        if ($fkName = $this->getForeignKeyName(self::TABLE, self::COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        } else {
            // Fallback: best-effort drop by column signature.
            Schema::table(self::TABLE, function (Blueprint $table) {
                try { $table->dropForeign([self::COL]); } catch (\Throwable $e) {}
            });
        }

        // 2) Drop any non-primary indexes on the column.
        foreach ($this->getIndexesOnColumn(self::TABLE, self::COL) as $idx) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($idx) {
                try { $table->dropIndex($idx); } catch (\Throwable $e) {}
            });
        }

        // 3) Drop the column.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn(self::COL);
        });
    }

    public function down(): void
    {
        // Recreate column (nullable; original data cannot be recovered here).
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, self::COL)) {
                // Place after league_id; adjust if your layout differs.
                $col = $table->unsignedBigInteger(self::COL)->nullable()->after('league_id');
                $table->index(self::COL, self::INDEX_NAME);
            }
        });

        // Recreate FK to fixture_contestants(id) with a predictable name.
        Schema::table(self::TABLE, function (Blueprint $table) {
            try {
                $table->foreign(self::COL, self::FK_NAME)
                    ->references(self::REF_COL)->on(self::REF_TABLE)
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            } catch (\Throwable $e) {
                // Fallback if name collides.
                try {
                    $table->foreign(self::COL)
                        ->references(self::REF_COL)->on(self::REF_TABLE)
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();
                } catch (\Throwable $ignore) {}
            }
        });
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
