<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'contestants';
    private const LINK_TABLE = 'competitor_member';
    private const MEMBERS_TABLE = 'members';

    private const NEW_COL = 'member_id';
    private const AFTER_COL = 'division_id';
    private const CONTESTANTS_COMPETITOR_COL = 'competitor_id';
    private const LINK_COMPETITOR_COL = 'competitor_id';
    private const LINK_MEMBER_COL = 'member_id';

    // Use a safe FK name to avoid clashing with unknown existing names
    private const TARGET_FK_NAME = 'contestants_member_id_foreign';

    public function up(): void
    {
        // 1) Ensure the column exists (nullable first for safe backfill).
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, self::NEW_COL)) {
                $table->unsignedBigInteger(self::NEW_COL)->nullable()->after(self::AFTER_COL);
            }
        });

        // 2) Backfill from competitor_member using contestants.competitor_id (dedupe with MAX()).
        DB::statement(sprintf(
            'UPDATE `%1$s` c
               JOIN (
                    SELECT `%3$s` AS cid, MAX(`%4$s`) AS mid
                      FROM `%2$s`
                     GROUP BY `%3$s`
               ) cm ON cm.cid = c.`%5$s`
               SET c.`%6$s` = cm.mid
             WHERE c.`%6$s` IS NULL',
            self::TABLE,
            self::LINK_TABLE,
            self::LINK_COMPETITOR_COL,
            self::LINK_MEMBER_COL,
            self::CONTESTANTS_COMPETITOR_COL,
            self::NEW_COL
        ));

        // 3) Drop existing FK on member_id if it exists (discover real name).
        if ($existingFk = $this->getForeignKeyName(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existingFk) {
                $table->dropForeign($existingFk); // drop by actual name
            });
        }

        // 4) If column currently has no FK, add it with a safe, consistent name.
        if (! $this->hasForeignOnColumn(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign(self::NEW_COL, self::TARGET_FK_NAME)
                    ->references('id')->on(self::MEMBERS_TABLE)
                    ->cascadeOnUpdate()
                    ->restrictOnDelete(); // change to ->cascadeOnDelete() if you prefer
            });
        }

        // 5) Ensure backfill complete, then enforce NOT NULL without doctrine/dbal.
        $nulls = (int) DB::table(self::TABLE)->whereNull(self::NEW_COL)->count();
        if ($nulls > 0) {
            throw new \RuntimeException("Backfill incomplete: {$nulls} rows still NULL in ".self::TABLE.'.'.self::NEW_COL);
        }

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
