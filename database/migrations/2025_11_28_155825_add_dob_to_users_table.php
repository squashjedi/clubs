<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'dob')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('dob')
                    ->nullable()
                    ->after('gender');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'dob')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('dob');
            });
        }
    }
};
