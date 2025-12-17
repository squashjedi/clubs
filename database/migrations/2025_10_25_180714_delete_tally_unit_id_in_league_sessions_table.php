<?php
// database/migrations/2025_10_25_091500_drop_tally_unit_id_from_league_sessions.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE     = 'league_sessions';
    private const COL       = 'tally_unit_id';
    private const REF_TABLE = 'tally_units';
    private const REF_COL   = 'id';

    // Names used when recreating in down()
    private const INDEX_NAME = 'league_sessions_tally_unit_id_index';
    private const FK_NAME    = 'league_sessions_tally_unit_id_foreign';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return; // nothing to do
        }

        // 1) Drop FK by discovered name if present
        if ($fk = $this->getForeignKeyName(self::TABLE, self::COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk);
            });
        } else {
            // Best-effort fallback
            Schema::table(self::TABLE, function (Blueprint $table) {
                try { $table->dropForeign([self::COL]); } catch (\Throwable $e) {}
            });
        }

        // 2) Drop any non-primary indexes on the column (defensive)
        foreach ($this->getIndexesOnColumn(self::TABLE, self::COL) as $idx) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($idx) {
                try { $table->dropIndex($idx); } catch (\Throwable $e) {}
            });
        }

        // 3) Drop the column
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn(self::COL);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) return;

        // Recreate column (nullable; data cannot be restored)
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, self::COL)) {
                $col = $table->unsignedBigInteger(self::COL)->nullable()->after('league_id');
                $table->index(self::COL, self::INDEX_NAME);
            }
        });

        // Recreate FK to tally_units(id)
        if (Schema::hasColumn(self::TABLE, self::COL) && Schema::hasTable(self::REF_TABLE)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                try {
                    $table->foreign(self::COL, self::FK_NAME)
                        ->references(self::REF_COL)->on(self::REF_TABLE)
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();
                } catch (\Throwable $e) {
                    // Fallback without explicit name
                    try {
                        $table->foreign(self::COL)
                            ->references(self::REF_COL)->on(self::REF_TABLE)
                            ->cascadeOnUpdate()
                            ->restrictOnDelete();
                    } catch (\Throwable $ignore) {}
                }
            });
        }
    }

    /** Return FK constraint name for table.column, or null if none. */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $db  = DB::getDatabaseName();
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

    /** @return string[] index names on the column (excluding PRIMARY). */
    private function getIndexesOnColumn(string $table, string $column): array
    {
        $db   = DB::getDatabaseName();
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
