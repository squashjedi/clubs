<?php
// database/migrations/2025_10_25_064500_rename_starting_at_to_starts_at_in_league_sessions.php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'league_sessions';
    private const OLD   = 'validated_at';
    private const NEW   = 'built_at';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: '.self::TABLE);
        }

        // If already renamed, nothing to do.
        if (Schema::hasColumn(self::TABLE, self::NEW) && !Schema::hasColumn(self::TABLE, self::OLD)) {
            return;
        }

        if (!Schema::hasColumn(self::TABLE, self::OLD)) {
            throw new \RuntimeException(sprintf('Column `%s` not found on `%s`.', self::OLD, self::TABLE));
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            // Older Laravel may require doctrine/dbal for renameColumn.
            $table->renameColumn(self::OLD, self::NEW);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) return;
        if (!Schema::hasColumn(self::TABLE, self::NEW)) return;

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->renameColumn(self::NEW, self::OLD);
        });
    }
};
