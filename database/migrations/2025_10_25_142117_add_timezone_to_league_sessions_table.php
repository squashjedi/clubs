<?php
// database/migrations/2025_10_25_064000_add_timezone_europe_london_after_league_id_to_league_sessions.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'league_sessions';
    private const COL   = 'timezone';
    private const AFTER = 'league_id';
    private const LEN   = 64; // adjust if you prefer

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: '.self::TABLE);
        }

        // 1) Add as nullable first so existing rows don't fail.
        if (!Schema::hasColumn(self::TABLE, self::COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->string(self::COL, self::LEN)->nullable()->after(self::AFTER);
            });
        }

        // 2) Set all rows to 'Europe/London'.
        DB::table(self::TABLE)->whereNull(self::COL)->orWhere(self::COL, '!=', 'Europe/London')
            ->update([self::COL => 'Europe/London']);

        // 3) Enforce NOT NULL (no default).
        DB::statement(sprintf(
            'ALTER TABLE `%s` MODIFY `%s` VARCHAR(%d) NOT NULL',
            self::TABLE, self::COL, self::LEN
        ));
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) return;
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, self::COL)) {
                $table->dropColumn(self::COL);
            }
        });
    }
};
