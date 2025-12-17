<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // First drop the existing foreign key (it won't allow changing the column)
        Schema::table('entrants', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        // Now make the column nullable
        Schema::table('entrants', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable()->change();
        });

        // Re-add the FK but allow NULL and choose desired delete behavior
        Schema::table('entrants', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')->on('members')
                ->nullOnDelete(); // or ->cascadeOnDelete() if you prefer
        });
    }

    public function down(): void
    {
        // Reverse: make it NOT NULL again
        Schema::table('entrants', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('entrants', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable(false)->change();
        });

        Schema::table('entrants', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')->on('members')
                ->restrictOnDelete();
        });
    }
};
