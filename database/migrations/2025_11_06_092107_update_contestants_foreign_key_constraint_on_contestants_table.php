<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contestants', function (Blueprint $table) {
            $table->dropForeign(['league_session_id']);

            $table->foreign('league_session_id')
                ->references('id')
                ->on('league_sessions')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('contestants', function (Blueprint $table) {
            $table->dropForeign(['league_session_id']);

            $table->foreign('league_session_id')
                ->references('id')
                ->on('league_sessions')
                ->onDelete('restrict');
        });
    }
};
