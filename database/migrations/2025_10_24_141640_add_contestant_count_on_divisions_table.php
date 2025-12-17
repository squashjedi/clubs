<?php
// database/migrations/2025_10_25_052500_add_contestant_count_to_divisions.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const DIVISIONS = 'divisions';
    private const CONTESTANTS = 'contestants';
    private const NEW_COL = 'contestant_count';
    private const AFTER_COL = 'index';

    public function up(): void
    {
        // Guards: ensure required tables/cols exist
        if (!Schema::hasTable(self::DIVISIONS)) {
            throw new \RuntimeException('Missing table: divisions');
        }
        if (!Schema::hasColumn(self::DIVISIONS, 'id')) {
            throw new \RuntimeException('Missing column `id` on divisions.');
        }
        if (!Schema::hasTable(self::CONTESTANTS) || !Schema::hasColumn(self::CONTESTANTS, 'division_id')) {
            throw new \RuntimeException('Missing contestants table or contestants.division_id column.');
        }

        // 1) Add column (NOT NULL DEFAULT 0) placed after `index` if it exists.
        Schema::table(self::DIVISIONS, function (Blueprint $table) {
            if (!Schema::hasColumn(self::DIVISIONS, self::NEW_COL)) {
                $col = $table->unsignedInteger(self::NEW_COL)->default(0);
                if (Schema::hasColumn(self::DIVISIONS, self::AFTER_COL)) {
                    $col->after(self::AFTER_COL);
                }
            }
        });

        // 2) Backfill from contestants count per division; divisions without contestants -> 0
        DB::statement(sprintf(
            'UPDATE `%1$s` d
               LEFT JOIN (
                    SELECT `division_id`, COUNT(*) AS cnt
                      FROM `%2$s`
                     GROUP BY `division_id`
               ) c ON c.`division_id` = d.`id`
               SET d.`%3$s` = COALESCE(c.cnt, 0)',
            self::DIVISIONS,
            self::CONTESTANTS,
            self::NEW_COL
        ));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::DIVISIONS)) {
            return;
        }
        Schema::table(self::DIVISIONS, function (Blueprint $table) {
            if (Schema::hasColumn(self::DIVISIONS, self::NEW_COL)) {
                $table->dropColumn(self::NEW_COL);
            }
        });
    }
};
