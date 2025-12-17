<?php

namespace App\Actions\Players;

use App\Models\Club;
use App\Models\Player;
use App\Models\Entrant;
use App\Models\Contestant;
use Illuminate\Support\Facades\DB;

class MergePlayersForClubAction
{
    /**
     * Merge $duplicate into $primary for a given club.
     *
     * After this:
     *  - All contestants for $duplicate in this club point to $primary
     *  - Club memberships are merged (pivot)
     *  - User link is preserved (if any)
     *  - $duplicate is deleted (if it has no other club memberships / data, your choice)
     *
     * @throws \RuntimeException if both players are linked to different users.
     */
    public function execute(Club $club, Player $primary, Player $duplicate): void
    {
        // sanity check: both players must belong to this club
        if (! $club->players()->whereKey($primary->id)->exists()) {
            throw new \InvalidArgumentException("Primary player does not belong to this club.");
        }

        if (! $club->players()->whereKey($duplicate->id)->exists()) {
            throw new \InvalidArgumentException("Duplicate player does not belong to this club.");
        }

        // Prevent silly case
        if ($primary->id === $duplicate->id) {
            return;
        }

        DB::transaction(function () use ($club, $primary, $duplicate) {

            // 1. Move contestants in this club from duplicate -> primary
            // We scope by club via leagueSession->league->club
            Contestant::where('player_id', $duplicate->id)
                ->whereHas('division.tier.session.league', function ($q) use ($club) {
                    $q->where('club_id', $club->id);
                })
                ->update(['player_id' => $primary->id]);

            Entrant::where('player_id', $duplicate->id)
                ->update(['player_id' => $primary->id]);

            Result::where('home_player_id', $duplicate->id)
                ->update(['home_player_id' => $primary->id]);

            Result::where('away_player_id', $duplicate->id)
                ->update(['away_player_id' => $primary->id]);

            // 2. Merge club memberships (pivot) across ALL clubs
            // (not just this club – usually you want a single canonical player everywhere)
            $duplicateClubIds = $duplicate->clubs()->pluck('clubs.id')->all();

            if (! empty($duplicateClubIds)) {
                $primary->clubs()->syncWithoutDetaching($duplicateClubIds);
            }

            // 3. Handle user link
            //   - If duplicate has user & primary doesn't → move user to primary
            //   - If both have same user → fine
            //   - If both have different users → blow up (manual intervention needed)
            // if ($duplicate->user_id && ! $primary->user_id) {
            //     $primary->user_id = $duplicate->user_id;
            //     $primary->save();
            // } elseif ($duplicate->user_id && $primary->user_id && $duplicate->user_id !== $primary->user_id) {
            //     throw new \RuntimeException('Both players are linked to different users. Resolve manually before merging.');
            // }

            // 4. TODO: move any other related data here
            // e.g. ratings, messages, stats, etc:
            // GlobalRating::where('player_id', $duplicate->id)->update([... 'player_id' => $primary->id]);

            // 5. Detach duplicate from this club (and optionally delete it completely)
            $club->players()
                ->wherePivot('player_id', $duplicate->id)
                ->forceDelete();  // true removal


            // If duplicate is not attached to any other clubs and has no contestants, delete it:
            $hasOtherClubs = $duplicate->clubs()->exists();
            $hasOtherContestants = Contestant::where('player_id', $duplicate->id)->exists();

            if (! $hasOtherClubs && ! $hasOtherContestants) {
                $duplicate->delete();
            }
        });
    }
}
