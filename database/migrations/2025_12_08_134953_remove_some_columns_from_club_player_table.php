<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_player', function (Blueprint $table) {
            // Just drop the columns – no foreign keys to remove
            $table->dropColumn(['user_id', 'first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::table('club_player', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // (Optional) if you actually want a real FK back:
            // $table->foreign('user_id')
            //     ->references('id')
            //     ->on('users')
            //     ->onDelete('cascade');
        });
    }
};
