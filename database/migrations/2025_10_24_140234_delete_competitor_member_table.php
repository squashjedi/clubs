<?php
// database/migrations/2025_10_25_050000_drop_competitor_member_table.php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'competitor_member';
    private const COMPETITORS = 'competitors';
    private const MEMBERS = 'members';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        // Why: ensures drop doesn't fail due to referencing FKs.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists(self::TABLE);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        // Recreate as a typical pivot table. Adjust if your schema differs.
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger('competitor_id');
            $table->unsignedBigInteger('member_id');

            $table->primary(['competitor_id', 'member_id'], 'competitor_member_pk');

            $table->foreign('competitor_id', 'competitor_member_competitor_id_fk')
                  ->references('id')->on(self::COMPETITORS)
                  ->cascadeOnUpdate()->cascadeOnDelete(); // adjust to restrict if needed

            $table->foreign('member_id', 'competitor_member_member_id_fk')
                  ->references('id')->on(self::MEMBERS)
                  ->cascadeOnUpdate()->cascadeOnDelete();

            // Optional: keep if your original table had timestamps.
            $table->timestamps();
        });
    }
};
