<?php
// database/migrations/2025_12_02_000002_make_member_id_nullable_on_contestants_table.php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // WHY: MySQL needs FK dropped before altering nullability.
        Schema::table('contestants', function (Blueprint $table) {
            if (Schema::hasColumn('contestants', 'member_id')) {
                $table->dropForeign(['member_id']);
            }
        });

        Schema::table('contestants', function (Blueprint $table) {
            // Requires doctrine/dbal for change() on existing columns
            $table->unsignedBigInteger('member_id')->nullable()->change();
        });

        Schema::table('contestants', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')->on('members')
                ->nullOnDelete(); // set NULL if member is deleted
        });
    }

    public function down(): void
    {
        Schema::table('contestants', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('contestants', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable(false)->change();
        });

        Schema::table('contestants', function (Blueprint $table) {
            // Adjust to your original policy if different
            $table->foreign('member_id')
                ->references('id')->on('members')
                ->cascadeOnDelete();
        });
    }
};
