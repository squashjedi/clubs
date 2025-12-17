<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const LEAGUES_TABLE = 'leagues';
    private const CLUBS_TABLE = 'clubs';
    private const NEW_COL = 'club_id';
    private const AFTER_COL = 'match_at';
    private const LINK_COL = 'league_id'; // FK on results -> leagues.id
    private const FK_NAME = 'results_club_id_foreign'; // stable name to avoid collisions

    public function up(): void
    {
        // Add as NULLable first for safe backfill and placement.
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, self::NEW_COL)) {
                $table->unsignedBigInteger(self::NEW_COL)->nullable()->after(self::AFTER_COL);
            }
        });

        // Backfill from leagues.club_id via results.league_id.
        DB::statement(sprintf(
            'UPDATE `%1$s` r
               JOIN `%2$s` l ON l.`id` = r.`%3$s`
               SET r.`%4$s` = l.`%4$s`
             WHERE r.`%4$s` IS NULL',
            self::TABLE,
            self::LEAGUES_TABLE,
            self::LINK_COL,
            self::NEW_COL
        ));

        // Drop existing FK on club_id if it exists (discover actual name).
        if ($existingFk = $this->getForeignKeyName(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existingFk) {
                $table->dropForeign($existingFk);
            });
        }

        // Add FK to clubs(id) if none currently set.
        if (!$this->hasForeignOnColumn(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign(self::NEW_COL, self::FK_NAME)
                    ->references('id')->on(self::CLUBS_TABLE)
                    ->cascadeOnUpdate()
                    ->restrictOnDelete(); // change to ->cascadeOnDelete() or ->nullOnDelete() if desired
            });
        }

        // Ensure no NULLs remain before enforcing NOT NULL.
        $nulls = (int) DB::table(self::TABLE)->whereNull(self::NEW_COL)->count();
        if ($nulls > 0) {
            throw new \RuntimeException("Backfill incomplete: {$nulls} rows still NULL in ".self::TABLE.'.'.self::NEW_COL);
        }

        // Enforce NOT NULL without requiring doctrine/dbal.
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` BIGINT UNSIGNED NOT NULL',
            self::TABLE,
            self::NEW_COL
        ));
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

    /** True if any FK exists on table.column. */
    private function hasForeignOnColumn(string $table, string $column): bool
    {
        return (bool) $this->getForeignKeyName($table, $column);
    }
};

