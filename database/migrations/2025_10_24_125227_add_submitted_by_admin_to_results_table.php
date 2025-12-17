<?php
// database/migrations/2025_10_25_033500_make_submitted_by_admin_not_null_default0.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const NEW_COL = 'submitted_by_admin';
    private const AFTER_COL = 'submitted_by';
    private const FIXTURE_CONTESTANTS = 'fixture_contestants';
    private const FIXTURES = 'fixtures';

    public function up(): void
    {
        // Guards
        foreach ([self::TABLE => [self::AFTER_COL, 'fixture_contestant_id'],
                  self::FIXTURE_CONTESTANTS => ['id','fixture_id'],
                  self::FIXTURES => ['id','is_admin']] as $t => $cols) {
            if (!Schema::hasTable($t)) throw new \RuntimeException("Missing table: {$t}");
            foreach ($cols as $c) if (!Schema::hasColumn($t, $c)) throw new \RuntimeException("Missing column `{$c}` on `{$t}`.");
        }

        // 1) Ensure column exists, NOT NULL, DEFAULT 0, placed after submitted_by.
        if (!Schema::hasColumn(self::TABLE, self::NEW_COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                // Laravel tinyInteger doesn't enforce (1); we'll normalize with raw SQL next.
                $table->tinyInteger(self::NEW_COL)->default(0)->after(self::AFTER_COL);
            });
        }
        // Force exact definition + placement.
        DB::statement(sprintf(
            'ALTER TABLE `%s` CHANGE `%s` `%s` TINYINT(1) NOT NULL DEFAULT 0 AFTER `%s`',
            self::TABLE, self::NEW_COL, self::NEW_COL, self::AFTER_COL
        ));

        // 2) Backfill from fixtures.is_admin via join chain.
        DB::statement(sprintf(
            'UPDATE `%1$s` r
               JOIN `%2$s` fc ON fc.`id` = r.`fixture_contestant_id`
               JOIN `%3$s` f  ON f.`id`  = fc.`fixture_id`
               SET  r.`%4$s`  = f.`is_admin`',
            self::TABLE, self::FIXTURE_CONTESTANTS, self::FIXTURES, self::NEW_COL
        ));
        // Column remains NOT NULL DEFAULT 0 for any rows not matched by join.
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) return;
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, self::NEW_COL)) {
                $table->dropColumn(self::NEW_COL);
            }
        });
    }
};
