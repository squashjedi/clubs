<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('league_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->json('division_template')->default(DB::raw('(JSON_ARRAY())'));
            $table->json('structure')->default(DB::raw('(JSON_ARRAY())'));
            $table->string('timezone');
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->tinyInteger('pts_win')->default(0);
            $table->tinyInteger('pts_draw')->default(0);
            $table->tinyInteger('pts_per_set')->default(0);
            $table->tinyInteger('pts_play')->default(0);
            $table->datetime('built_at')->nullable();
            $table->datetime('published_at')->nullable();
            $table->datetime('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('league_sessions');
    }
};
