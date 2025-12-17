<?php
// database/migrations/2025_10_24_140000_add_league_session_id_to_contestants.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TARGET_TABLE = 'contestants';
    private const DIVISIONS_TABLE = 'divisions';
    private const LEAGUE_SESSIONS_TABLE = 'league_sessions';
    private const NEW_COL = 'league_session_id';
    private const DIVISION_ID_COL = 'division_id';
    private const FK_NAME = 'contestants_league_session_id_foreign'; // default convention

    public function up(): void
    {
        // 1) Add column nullable first (so we can backfill), placed after `id`.
        Schema::table(self::TARGET_TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TARGET_TABLE, self::NEW_COL)) {
                $table->unsignedBigInteger(self::NEW_COL)->nullable()->after('id');
            }
        });

        // 2) Backfill from divisions.league_session_id using existing contestants.division_id.
        DB::statement(sprintf(
            'UPDATE `%1$s` c
             INNER JOIN `%2$s` d ON d.`id` = c.`%3$s`
             SET c.`%4$s` = d.`%4$s`
             WHERE c.`%4$s` IS NULL',
            self::TARGET_TABLE,
            self::DIVISIONS_TABLE,
            self::DIVISION_ID_COL,
            self::NEW_COL
        ));

        // 3) Add FK constraint (creates supporting index automatically).
        Schema::table(self::TARGET_TABLE, function (Blueprint $table) {
            $table->foreign(self::NEW_COL, self::FK_NAME)
                ->references('id')
                ->on(self::LEAGUE_SESSIONS_TABLE)
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // adjust if you need SET NULL or CASCADE
        });

        // 4) Ensure no NULLs remain before making NOT NULL.
        $nulls = (int) DB::table(self::TARGET_TABLE)->whereNull(self::NEW_COL)->count();
        if ($nulls > 0) {
            // Why: failing here protects you from locking the table with an invalid NOT NULL alter.
            throw new RuntimeException(sprintf(
                'Backfill incomplete: %d %s.%s rows are NULL. Fix data before rerunning.',
                $nulls,
                self::TARGET_TABLE,
                self::NEW_COL
            ));
        }

        // 5) Make the column NOT NULL (raw SQL to avoid requiring doctrine/dbal).
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` BIGINT UNSIGNED NOT NULL',
            self::TARGET_TABLE,
            self::NEW_COL
        ));
    }

    public function down(): void
    {

    }
};

