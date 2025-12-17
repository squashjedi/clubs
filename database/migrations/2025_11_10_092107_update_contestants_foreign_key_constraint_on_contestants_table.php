<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contestants', function (Blueprint $table) {
            $table->dropForeign(['division_id']);

            $table->foreign('division_id')
                ->references('id')
                ->on('divisions')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('contestants', function (Blueprint $table) {
            $table->dropForeign(['division_id']);

            $table->foreign('division_id')
                ->references('id')
                ->on('divisions')
                ->onDelete('restrict');
        });
    }
};
