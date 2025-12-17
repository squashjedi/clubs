<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();

            // Identity fields
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->index();
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');
            $table->string('tel_no')->nullable();
            $table->date('dob')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
