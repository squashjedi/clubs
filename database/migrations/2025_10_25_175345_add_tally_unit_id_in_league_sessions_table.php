<?php
// database/migrations/2025_10_25_090000_add_tally_unit_id_after_template_to_leagues.php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'leagues';
    private const COL   = 'tally_unit_id';
    private const AFTER = 'template';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: '.self::TABLE);
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, self::COL)) {
                $col = $table->unsignedBigInteger(self::COL)->nullable();
                if (Schema::hasColumn(self::TABLE, self::AFTER)) {
                    $col->after(self::AFTER);
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, self::COL)) {
                $table->dropColumn(self::COL);
            }
        });
    }
};
