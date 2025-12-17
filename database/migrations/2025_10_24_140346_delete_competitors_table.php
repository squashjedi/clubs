<?php
// database/migrations/2025_10_25_050500_drop_competitors_table.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'competitors';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        // Disable FKs to avoid failures from referencing tables.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists(self::TABLE);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        // Minimal stub; replace with your exact columns if needed.
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });
    }
};
