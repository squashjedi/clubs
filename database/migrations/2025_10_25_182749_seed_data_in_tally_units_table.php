<?php

use App\Models\TallyUnit;
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
        TallyUnit::create(['name' => 'Sets', 'key' => 'sets']);
        TallyUnit::create(['name' => 'Games', 'key' => 'games']);
        TallyUnit::create(['name' => 'Points', 'key' => 'points']);
        TallyUnit::create(['name' => 'Frames', 'key' => 'frames']);
        TallyUnit::create(['name' => 'Legs', 'key' => 'legs']);
        TallyUnit::create(['name' => 'Racks', 'key' => 'racks']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tally_units', function (Blueprint $table) {
            //
        });
    }
};
