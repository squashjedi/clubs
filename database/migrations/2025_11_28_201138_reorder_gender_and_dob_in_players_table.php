<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {

            // Move gender after last_name (only if exists)
            if (Schema::hasColumn('players', 'gender')) {
                $table->string('gender')
                    ->nullable()
                    ->change()
                    ->after('last_name');
            }

            // Move dob after gender (only if exists)
            if (Schema::hasColumn('players', 'dob')) {
                $table->date('dob')
                    ->nullable()
                    ->change()
                    ->after('gender');
            }
        });
    }

    public function down(): void
    {
        // Reordering columns is not usually safely reversible,
        // so leaving this empty intentionally.
    }
};
