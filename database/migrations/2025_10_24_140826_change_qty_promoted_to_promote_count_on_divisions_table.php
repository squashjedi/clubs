<?php
// database/migrations/2025_10_25_051000_rename_qty_promoted_to_promote_count_in_divisions.php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'divisions';
    private const OLD   = 'qty_promoted';
    private const NEW   = 'promote_count';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            throw new \RuntimeException('Missing table: ' . self::TABLE);
        }
        if (!Schema::hasColumn(self::TABLE, self::OLD)) {
            // Already renamed or missing; nothing to do if NEW exists.
            if (!Schema::hasColumn(self::TABLE, self::NEW)) {
                throw new \RuntimeException(sprintf('Neither `%s` nor `%s` exists on `%s`.', self::OLD, self::NEW, self::TABLE));
            }
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->renameColumn(self::OLD, self::NEW); // may require doctrine/dbal on older Laravel
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::NEW)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->renameColumn(self::NEW, self::OLD);
        });
    }
};
