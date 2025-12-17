<?php
// database/migrations/2025_10_25_043500_drop_diff_from_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const COL   = 'w';
    private const AFTER = 'away_score'; // adjust if your layout differs

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return; // already dropped or table missing
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn(self::COL);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, self::COL)) {
                $col = $table->integer(self::COL)->nullable();
                // Position is cosmetic; MySQL ignores AFTER if column not found.
                if (Schema::hasColumn(self::TABLE, self::AFTER)) {
                    $col->after(self::AFTER);
                }
            }
        });
    }
};
