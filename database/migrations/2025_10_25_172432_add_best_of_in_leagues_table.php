<?php
// database/migrations/2025_10_25_081000_add_best_of_after_template_to_leagues.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const LEAGUES          = 'leagues';
    private const NEW_COL          = 'best_of';
    private const AFTER_COL        = 'template';
    private const SESSIONS         = 'league_sessions';
    private const SESSIONS_FK      = 'league_id';
    private const SESSIONS_BEST_OF = 'best_of';
    private const FALLBACK         = 5;

    public function up(): void
    {
        // Guards
        if (!Schema::hasTable(self::LEAGUES)) {
            throw new \RuntimeException('Missing table: leagues');
        }
        if (!Schema::hasColumn(self::LEAGUES, 'id')) {
            throw new \RuntimeException('Missing column leagues.id');
        }
        if (!Schema::hasColumn(self::LEAGUES, self::AFTER_COL)) {
            throw new \RuntimeException('Missing column leagues.'.self::AFTER_COL.' (needed for placement)');
        }
        if (!Schema::hasTable(self::SESSIONS) || !Schema::hasColumn(self::SESSIONS, self::SESSIONS_FK) || !Schema::hasColumn(self::SESSIONS, self::SESSIONS_BEST_OF)) {
            throw new \RuntimeException('Missing league_sessions or required columns (league_id, best_of)');
        }

        // 1) Add as NULLable first for safe backfill; place after `template`.
        Schema::table(self::LEAGUES, function (Blueprint $table) {
            if (!Schema::hasColumn(self::LEAGUES, self::NEW_COL)) {
                $table->tinyInteger(self::NEW_COL)->nullable()->after(self::AFTER_COL);
            }
        });

        // 2) Backfill from the FIRST league_session per league (smallest id); fallback to 5.
        DB::statement(sprintf(
            'UPDATE `%1$s` l
               LEFT JOIN (
                    SELECT ls.`%3$s` AS league_id, ls.`%4$s` AS best_of
                      FROM `%2$s` ls
                      JOIN (
                            SELECT `%3$s` AS league_id, MIN(`id`) AS min_id
                              FROM `%2$s`
                             GROUP BY `%3$s`
                      ) firsts ON firsts.league_id = ls.`%3$s` AND firsts.min_id = ls.`id`
               ) x ON x.league_id = l.`id`
               SET l.`%5$s` = COALESCE(x.best_of, %6$d)
             WHERE l.`%5$s` IS NULL',
            self::LEAGUES,            // 1
            self::SESSIONS,           // 2
            self::SESSIONS_FK,        // 3
            self::SESSIONS_BEST_OF,   // 4
            self::NEW_COL,            // 5
            self::FALLBACK            // 6
        ));

        // 3) Enforce NOT NULL (no default); keep TINYINT.
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` TINYINT NOT NULL',
            self::LEAGUES, self::NEW_COL
        ));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::LEAGUES)) {
            return;
        }
        Schema::table(self::LEAGUES, function (Blueprint $table) {
            if (Schema::hasColumn(self::LEAGUES, self::NEW_COL)) {
                $table->dropColumn(self::NEW_COL);
            }
        });
    }
};
