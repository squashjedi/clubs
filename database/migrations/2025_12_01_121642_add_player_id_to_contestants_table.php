<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contestants', function (Blueprint $table) {
            if (! Schema::hasColumn('contestants', 'player_id')) {

                $table->unsignedBigInteger('player_id')
                    ->nullable()
                    ->after('member_id');

                $table->foreign('player_id')
                    ->references('id')
                    ->on('players')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contestants', function (Blueprint $table) {
            if (Schema::hasColumn('contestants', 'player_id')) {
                $table->dropForeign(['player_id']);
                $table->dropColumn('player_id');
            }
        });
    }
};
