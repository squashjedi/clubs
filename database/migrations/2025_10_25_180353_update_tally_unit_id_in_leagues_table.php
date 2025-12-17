<?php
// database/migrations/2025_10_25_090500_set_all_leagues_tally_unit_id_to_2.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'leagues';
    private const COL   = 'tally_unit_id';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            throw new \RuntimeException(sprintf('Missing %s.%s', self::TABLE, self::COL));
        }
        DB::table(self::TABLE)->update([self::COL => 2]);
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return;
        }
        DB::table(self::TABLE)->update([self::COL => null]);
    }
};
