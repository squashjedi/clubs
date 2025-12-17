<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateResultsForeignKeysToCascadeToResultsTable extends Migration
{
    public function up()
    {
        Schema::table('results', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['home_contestant_id']);
            $table->dropForeign(['away_contestant_id']);

            // Re-add with CASCADE delete
            $table->foreign('home_contestant_id')
                  ->references('id')
                  ->on('contestants')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('away_contestant_id')
                  ->references('id')
                  ->on('contestants')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('results', function (Blueprint $table) {
            // Drop the cascade constraints
            $table->dropForeign(['home_contestant_id']);
            $table->dropForeign(['away_contestant_id']);

            // Re-add with RESTRICT (original behavior)
            $table->foreign('home_contestant_id')
                  ->references('id')
                  ->on('contestants')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('away_contestant_id')
                  ->references('id')
                  ->on('contestants')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }
}
