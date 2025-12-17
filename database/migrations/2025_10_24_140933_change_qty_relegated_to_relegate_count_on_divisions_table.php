<?php
// database/migrations/2025_10_25_051500_rename_qty_relegated_to_relegate_count_in_divisions.php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'divisions';
    private const OLD   = 'qty_relegated';
    private const NEW   = 'relegate_count';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: '.self::TABLE);
        }

        // If already renamed, no-op.
        if (Schema::hasColumn(self::TABLE, self::NEW) && !Schema::hasColumn(self::TABLE, self::OLD)) {
            return;
        }

        if (!Schema::hasColumn(self::TABLE, self::OLD)) {
            throw new \RuntimeException(sprintf('Column `%s` not found on `%s`.', self::OLD, self::TABLE));
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->renameColumn(self::OLD, self::NEW);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        if (Schema::hasColumn(self::TABLE, self::OLD)) {
            return; // already reverted
        }

        if (!Schema::hasColumn(self::TABLE, self::NEW)) {
            return; // nothing to do
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->renameColumn(self::NEW, self::OLD);
        });
    }
};
