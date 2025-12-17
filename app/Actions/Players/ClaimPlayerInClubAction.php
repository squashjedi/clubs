<?php

namespace App\Actions\Players;

use App\Models\Club;
use App\Models\Player;
use App\Models\Result;
use App\Models\Entrant;
use App\Models\Contestant;
use App\Models\Invitation;
use App\Enums\PlayerRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClaimPlayerInClubAction
{
    public function execute(Club $club, Player $authUserPlayer = null, Player $clubPlayer, Invitation $invitation)
    {
        $player = DB::transaction(function () use ($club, $authUserPlayer, $clubPlayer, $invitation) {
            if (is_null($authUserPlayer)) {
                Auth::user()->players()->attach($clubPlayer->id, [
                    'relationship' => PlayerRelationship::Guardian
                ]);

                $clubPlayer->update([
                    'email' => Auth::user()->email,
                    'tel_no' => Auth::user()->tel_no,
                ]);

                $invitation->delete();

                return $clubPlayer;
            } else {
                Contestant::where('player_id', $clubPlayer->id)
                    ->whereHas('division.tier.session.league', function ($q) use ($club) {
                        $q->where('club_id', $club->id);
                    })
                    ->update(['player_id' => $authUserPlayer->id]);

                Entrant::where('player_id', $clubPlayer->id)
                    ->update(['player_id' => $authUserPlayer->id]);

                Result::where('home_player_id', $clubPlayer->id)
                    ->update(['home_player_id' => $authUserPlayer->id]);

                Result::where('away_player_id', $clubPlayer->id)
                    ->update(['away_player_id' => $authUserPlayer->id]);

                $clubPlayer->clubs()->updateExistingPivot($club->id, [
                    'player_id' => $authUserPlayer->id,
                ]);

                $clubPlayer->forceDelete();

                $invitation->delete();

                return $authUserPlayer;
            }
        });

        return $player;
    }
}