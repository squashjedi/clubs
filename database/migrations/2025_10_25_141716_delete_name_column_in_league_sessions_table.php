<?php
// database/migrations/2025_10_25_063000_drop_name_from_league_sessions.php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'league_sessions';
    private const COL   = 'name';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            return; // nothing to do
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
                // Adjust type/placement to match your original schema if different
                $col = $table->string(self::COL)->nullable();
                if (Schema::hasColumn(self::TABLE, 'id')) {
                    $col->after('id');
                }
            }
        });
    }
};
