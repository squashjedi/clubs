<?php
// database/migrations/2025_10_24_220000_rename_fixture_at_to_match_at_in_results.php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'results';
    private const OLD = 'fixture_at';
    private const NEW = 'match_at';

    public function up(): void
    {
        // Requires doctrine/dbal on older Laravel when renaming columns.
        if (Schema::hasColumn(self::TABLE, self::OLD) && !Schema::hasColumn(self::TABLE, self::NEW)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->renameColumn(self::OLD, self::NEW);
            });
        }
    }

    public function down(): void
    {

    }
};
