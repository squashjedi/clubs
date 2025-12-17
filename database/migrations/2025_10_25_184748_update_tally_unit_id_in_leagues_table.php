<?php
// database/migrations/2025_10_25_092500_add_fk_tally_unit_id_on_leagues.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE        = 'leagues';
    private const COL          = 'tally_unit_id';
    private const REF_TABLE    = 'tally_units';
    private const REF_COL      = 'id';
    private const FK_NAME      = 'leagues_tally_unit_id_fk';

    public function up(): void
    {
        // Guards
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COL)) {
            throw new \RuntimeException('Missing leagues.tally_unit_id');
        }
        if (!Schema::hasTable(self::REF_TABLE) || !Schema::hasColumn(self::REF_TABLE, self::REF_COL)) {
            throw new \RuntimeException('Missing tally_units.id');
        }

        // Drop existing FK if present (prevents duplicate-name errors)
        if ($existing = $this->getForeignKeyName(self::TABLE, self::COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existing) {
                $table->dropForeign($existing);
            });
        }

        // Create FK
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->foreign(self::COL, self::FK_NAME)
                ->references(self::REF_COL)->on(self::REF_TABLE)
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // change to ->nullOnDelete() / ->cascadeOnDelete() if desired
        });
    }

    public function down(): void
    {
        // Drop FK by discovered name if present; fallback to known name
        if ($existing = $this->getForeignKeyName(self::TABLE, self::COL)) {
            Schema::table(self::TABLE, function (Blueprint $table) use ($existing) {
                try { $table->dropForeign($existing); } catch (\Throwable $e) {}
            });
        } else {
            Schema::table(self::TABLE, function (Blueprint $table) {
                try { $table->dropForeign(self::FK_NAME); } catch (\Throwable $e) {}
                try { $table->dropForeign([self::COL]); } catch (\Throwable $e) {}
            });
        }
    }

    /** Discover FK constraint name on table.column, or null if none. */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $db  = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME
               FROM information_schema.KEY_COLUMN_USAGE
              WHERE CONSTRAINT_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
              LIMIT 1',
            [$db, $table, $column]
        );
        return $row->CONSTRAINT_NAME ?? null;
    }
};
