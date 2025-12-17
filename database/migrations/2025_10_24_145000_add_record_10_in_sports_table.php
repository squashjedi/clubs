<?php
// database/migrations/2025_10_25_062000_insert_padel_tennis_into_sports.php
declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    private const TABLE = 'sports';
    private const NAME  = 'Padel Tennis';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, 'name')) {
            throw new \RuntimeException('Missing `sports` table or `name` column.');
        }

        $exists = DB::table(self::TABLE)->where('name', self::NAME)->exists();
        if ($exists) {
            return; // already inserted
        }

        $data = ['name' => self::NAME];

        // If timestamps exist, populate them.
        $now = Carbon::now();
        if (Schema::hasColumn(self::TABLE, 'created_at')) {
            $data['created_at'] = $now;
        }
        if (Schema::hasColumn(self::TABLE, 'updated_at')) {
            $data['updated_at'] = $now;
        }

        DB::table(self::TABLE)->insert($data);
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, 'name')) {
            return;
        }
        DB::table(self::TABLE)->where('name', self::NAME)->delete();
    }
};
