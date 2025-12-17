<?php
// database/migrations/2025_10_25_012500_add_home_contestant_id_after_division_id_to_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const NEW_COL = 'home_contestant_id';
    private const AFTER_COL = 'division_id';             // << placed here
    private const FIXTURE_CONTESTANTS = 'fixture_contestants';
    private const CONTESTANTS = 'contestants';
    private const FK_NAME = 'results_home_contestant_id_foreign';

    public function up(): void
    {
        // Add as NULLable for safe backfill (placed after division_id).
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, self::NEW_COL)) {
                $table->unsignedBigInteger(self::NEW_COL)->nullable()->after(self::AFTER_COL);
            }
        });

        // Backfill from fixture_contestants.contestant_id via results.fixture_contestant_id.
        DB::statement(sprintf(
            'UPDATE `%1$s` r
               JOIN `%2$s` fc ON fc.`id` = r.`fixture_contestant_id`
               SET  r.`%3$s`  = fc.`contestant_id`
             WHERE r.`%3$s` IS NULL',
            self::TABLE, self::FIXTURE_CONTESTANTS, self::NEW_COL
        ));

        // Drop existing FK on the column if present.
        if ($existingFk = $this->getForeignKeyName(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existingFk) {
                $table->dropForeign($existingFk);
            });
        }

        // (Re)create FK to contestants(id) if none exists.
        if (!$this->hasForeignOnColumn(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign(self::NEW_COL, self::FK_NAME)
                    ->references('id')->on(self::CONTESTANTS)
                    ->cascadeOnUpdate()
                    ->restrictOnDelete(); // change to ->cascadeOnDelete() / ->nullOnDelete() if desired
            });
        }

        // Ensure no NULLs remain, then enforce NOT NULL.
        $nulls = (int) DB::table(self::TABLE)->whereNull(self::NEW_COL)->count();
        if ($nulls > 0) {
            throw new \RuntimeException("Backfill incomplete: {$nulls} rows still NULL in ".self::TABLE.'.'.self::NEW_COL);
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` BIGINT UNSIGNED NOT NULL',
            self::TABLE, self::NEW_COL
        ));
    }

    public function down(): void
    {
        // Drop FK by discovered name if present, then drop the column.
        if ($existingFk = $this->getForeignKeyName(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existingFk) {
                try { $table->dropForeign($existingFk); } catch (\Throwable $e) {}
            });
        } else {
            Schema::table(self::TABLE, function (Blueprint $table) {
                try { $table->dropForeign(self::FK_NAME); } catch (\Throwable $e) {}
                try { $table->dropForeign([self::NEW_COL]); } catch (\Throwable $e) {}
            });
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, self::NEW_COL)) {
                $table->dropColumn(self::NEW_COL);
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

    /** True if any FK exists on table.column. */
    private function hasForeignOnColumn(string $table, string $column): bool
    {
        return (bool) $this->getForeignKeyName($table, $column);
    }
};
