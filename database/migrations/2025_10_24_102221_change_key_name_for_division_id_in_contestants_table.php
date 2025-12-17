<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'contestants';
    private const COL = 'division_id';
    private const REF_TABLE = 'divisions';
    private const REF_COL = 'id';
    private const TARGET_INDEX = 'contestants_division_id_foreign'; // desired Key_name (index)
    private const TARGET_FK = 'contestants_division_id_foreign';     // desired FK name
    private const TARGET_FK_FALLBACK = 'contestants_division_id_fk';  // fallback if name clashes

    public function up(): void
    {
        // 0) Ensure column exists (safety).
        if (! Schema::hasColumn(self::TABLE, self::COL)) {
            throw new RuntimeException(self::TABLE.'.'.self::COL.' does not exist.');
        }

        // 1) Drop existing FK on division_id (any name).
        if ($fkName = $this->getForeignKeyName(self::TABLE, self::COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

        // 2) Drop any existing non-target indexes on division_id.
        foreach ($this->getIndexesOnColumn(self::TABLE, self::COL) as $idx) {
            if ($idx !== self::TARGET_INDEX) {
                Schema::table(self::TABLE, function (Blueprint $table) use ($idx) {
                    // Why: we want a clean, single, predictably named index
                    $table->dropIndex($idx);
                });
            }
        }

        // 3) Create (or ensure) the index with the desired name.
        if (! in_array(self::TARGET_INDEX, $this->getIndexesOnColumn(self::TABLE, self::COL), true)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(self::COL, self::TARGET_INDEX);
            });
        }

        // 4) Create FK with the desired name. If MySQL rejects due to name clash, use fallback.
        try {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign(self::COL, self::TARGET_FK)
                    ->references(self::REF_COL)->on(self::REF_TABLE)
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (\Throwable $e) {
            // Some MySQL setups disallow an FK and index sharing the same name.
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign(self::COL, self::TARGET_FK_FALLBACK)
                    ->references(self::REF_COL)->on(self::REF_TABLE)
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Drop FK by either target name or fallback, then drop the named index.
        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach ([self::TARGET_FK, self::TARGET_FK_FALLBACK] as $fk) {
                try { $table->dropForeign($fk); } catch (\Throwable $e) {}
            }
            try { $table->dropIndex(self::TARGET_INDEX); } catch (\Throwable $e) {}
        });
    }

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

    /** @return string[] index names on a given column (excluding PRIMARY). */
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
