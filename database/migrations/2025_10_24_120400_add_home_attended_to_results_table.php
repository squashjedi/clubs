<?php
// database/migrations/2025_10_25_023000_add_home_attended_after_away_score_to_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const NEW_COL = 'home_attended';
    private const AFTER_COL = 'away_score';
    private const FIXTURE_CONTESTANTS = 'fixture_contestants';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: ' . self::TABLE);
        }
        if (!Schema::hasColumn(self::TABLE, 'fixture_contestant_id')) {
            throw new \RuntimeException('results.fixture_contestant_id is required for backfill.');
        }
        if (!Schema::hasColumn(self::TABLE, self::AFTER_COL)) {
            throw new \RuntimeException(sprintf('Missing column `%s` on `%s` for placement.', self::AFTER_COL, self::TABLE));
        }

        // 1) Ensure the column exists as NOT NULL DEFAULT 1 after away_score.
        if (!Schema::hasColumn(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                // Not nullable, default 1
                $table->tinyInteger(self::NEW_COL)->default(1)->after(self::AFTER_COL);
            });
        } else {
            // Set existing NULLs to 1 before enforcing NOT NULL
            DB::table(self::TABLE)->whereNull(self::NEW_COL)->update([self::NEW_COL => 1]);
        }

        // Normalize exact type/placement.
        DB::statement(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` TINYINT(1) NOT NULL DEFAULT 1 AFTER `%s`',
            self::TABLE, self::NEW_COL, self::NEW_COL, self::AFTER_COL
        ));

        // 2) Backfill from fixture_contestants.turned_up via results.fixture_contestant_id.
        //    Why: ensure column reflects real attendance flags, not just default.
        DB::statement(sprintf(
            'UPDATE `%1$s` r
               JOIN `%2$s` fc ON fc.`id` = r.`fixture_contestant_id`
               SET  r.`%3$s`  = fc.`turned_up`',
            self::TABLE, self::FIXTURE_CONTESTANTS, self::NEW_COL
        ));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, self::NEW_COL)) {
                $table->dropColumn(self::NEW_COL);
            }
        });
    }
};
