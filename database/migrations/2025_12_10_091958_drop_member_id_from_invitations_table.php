<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // First drop the FK constraint, then drop the column
            if (Schema::hasColumn('invitations', 'member_id')) {
                $table->dropForeign(['member_id']);
                $table->dropColumn('member_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // Recreate the column
            $table->foreignId('member_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
        });
    }
};
