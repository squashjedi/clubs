<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE club_player LIKE members');
        DB::statement('INSERT INTO club_player SELECT * FROM members');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS club_player');
    }
};
