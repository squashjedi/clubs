<?php

use App\Models\SportTallyUnit;
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
        SportTallyUnit::create(['sport_id' => 1, 'tally_unit_id' => 2, 'max_best_of' => 5]);
        SportTallyUnit::create(['sport_id' => 1, 'tally_unit_id' => 3, 'max_best_of' => 29]);
        SportTallyUnit::create(['sport_id' => 2, 'tally_unit_id' => 2, 'max_best_of' => 5]);
        SportTallyUnit::create(['sport_id' => 2, 'tally_unit_id' => 3, 'max_best_of' => 41]);
        SportTallyUnit::create(['sport_id' => 3, 'tally_unit_id' => 1, 'max_best_of' => 5]);
        SportTallyUnit::create(['sport_id' => 3, 'tally_unit_id' => 2, 'max_best_of' => 11]);
        SportTallyUnit::create(['sport_id' => 4, 'tally_unit_id' => 4, 'max_best_of' => 35]);
        SportTallyUnit::create(['sport_id' => 5, 'tally_unit_id' => 1, 'max_best_of' => 13]);
        SportTallyUnit::create(['sport_id' => 5, 'tally_unit_id' => 5, 'max_best_of' => 35]);
        SportTallyUnit::create(['sport_id' => 6, 'tally_unit_id' => 2, 'max_best_of' => 35]);
        SportTallyUnit::create(['sport_id' => 7, 'tally_unit_id' => 2, 'max_best_of' => 7]);
        SportTallyUnit::create(['sport_id' => 7, 'tally_unit_id' => 3, 'max_best_of' => 21]);
        SportTallyUnit::create(['sport_id' => 8, 'tally_unit_id' => 2, 'max_best_of' => 5]);
        SportTallyUnit::create(['sport_id' => 8, 'tally_unit_id' => 3, 'max_best_of' => 29]);
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
