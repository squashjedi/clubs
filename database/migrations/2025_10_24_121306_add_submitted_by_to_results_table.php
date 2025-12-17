<?php
// database/migrations/2025_10_25_024500_add_submitted_by_after_away_attended_to_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const NEW_COL = 'submitted_by';
    private const AFTER_COL = 'away_attended';
    private const USERS_TABLE = 'users';
    private const FIXTURE_CONTESTANTS = 'fixture_contestants';
    private const FIXTURES = 'fixtures';
    private const FK_NAME = 'results_submitted_by_foreign';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Table `results` not found.');
        }
        if (!Schema::hasColumn(self::TABLE, 'fixture_contestant_id')) {
            throw new \RuntimeException('`results.fixture_contestant_id` is required for backfill.');
        }
        if (!Schema::hasColumn(self::TABLE, self::AFTER_COL)) {
            throw new \RuntimeException(sprintf('Missing `%s` on `%s` for placement.', self::AFTER_COL, self::TABLE));
        }

        // 1) Add column (nullable first) after away_attended.
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, self::NEW_COL)) {
                $table->unsignedBigInteger(self::NEW_COL)->nullable()->after(self::AFTER_COL);
            }
        });

        // 2) Backfill from fixtures.user_id via results -> fixture_contestants -> fixtures.
        DB::statement(sprintf(
            'UPDATE `%1$s` r
               JOIN `%2$s` fc ON fc.`id` = r.`fixture_contestant_id`
               JOIN `%3$s` f  ON f.`id`  = fc.`fixture_id`
               SET  r.`%4$s`  = f.`user_id`
             WHERE r.`%4$s` IS NULL',
            self::TABLE, self::FIXTURE_CONTESTANTS, self::FIXTURES, self::NEW_COL
        ));

        // 3) Drop existing FK (if any), then (re)create FK to users(id).
        if ($existingFk = $this->getForeignKeyName(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existingFk) {
                $table->dropForeign($existingFk); // Why: avoid duplicate-name errors.
            });
        }
        if (!$this->hasForeignOnColumn(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign(self::NEW_COL, self::FK_NAME)
                    ->references('id')->on(self::USERS_TABLE)
                    ->cascadeOnUpdate()
                    ->restrictOnDelete(); // change to ->cascadeOnDelete()/->nullOnDelete() if desired
            });
        }

        // 4) Ensure no NULLs remain; then enforce NOT NULL.
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
