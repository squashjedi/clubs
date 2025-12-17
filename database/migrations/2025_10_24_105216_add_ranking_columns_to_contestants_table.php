<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'contestants';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            // Why: adding conditionally avoids failures on partially migrated envs.
            if (!Schema::hasColumn(self::TABLE, 'overall_rank')) {
                $column = $table->unsignedInteger('overall_rank')->nullable();
                // Place after `index` if it exists; otherwise, Laravel ignores positioning.
                $column->after('index');
            }
        });

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (!Schema::hasColumn(self::TABLE, 'division_rank')) {
                $table->unsignedInteger('division_rank')->nullable()->after('overall_rank');
            }
        });
    }

    public function down(): void
    {

    }
};
