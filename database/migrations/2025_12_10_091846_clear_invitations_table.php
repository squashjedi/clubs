<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invitations')->delete();
    }

    public function down(): void
    {
        // Nothing to restore; leave empty on purpose
    }
};
