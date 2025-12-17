<?php

use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Make sure the Player model & helper are available here.
        // If you don't want to use the model, see the DB-only version below.
        Player::chunkById(500, function ($players) {
            foreach ($players as $player) {
                $player->first_name = format_name($player->first_name);
                $player->last_name  = format_name($player->last_name);
                $player->save();
            }
        });
    }

    public function down(): void
    {
        // Irreversible: we can't know original capitalization.
        // You can leave this empty or implement your own logic if needed.
    }
};
