<?php

use App\Models\League;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            League::withTrashed()->chunkById(50, function (Collection $leagues) {
                $leagues->each(function ($league) {
                    $league->sessions()->whereNotNull('processed_at')->orderBy('id')->chunkById(50, function (Collection $sessions) {
                        $sessions->each(function ($session) {

                            $tiers = collect($session->calculateStandings())->sortBy('index')->values();

                            if ($tiers->isEmpty()) return [];

                            $tiersByIndex = $tiers->keyBy(fn ($t) => (int) $t['index']);
                            $tierMin = (int) $tiers->min('index');
                            $tierMax = (int) $tiers->max('index');

                            // Normalize & sort one division’s standings (array|Collection -> array)
                            // IMPORTANT: no filtering — include trashed contestants.
                            $sortedRows = function ($divisionRows): array {
                                return collect($divisionRows ?? [])
                                    ->sortBy([['rank', 'asc'], ['seed', 'asc']])
                                    ->values()
                                    ->all();
                            };

                            // Pool a whole tier’s divisions for a specific slice ($getSlice returns rows for a division)
                            $poolTierRows = function ($tier, callable $getSlice) use ($sortedRows): array {
                                $divs = collect($tier['divisions'])->sortBy('index')->values();
                                $packs = [];

                                foreach ($divs as $div) {
                                    $rows  = $sortedRows(data_get($div, 'standings'));
                                    $slice = $getSlice($div, $rows); // returns [] or subset
                                    $packs[] = ['div' => $div, 'rows' => array_values($slice)];
                                }

                                // Row-wise pool left→right
                                $maxDepth = 0;
                                foreach ($packs as $p) {
                                    $maxDepth = max($maxDepth, count($p['rows']));
                                }

                                $ordered = [];
                                for ($i = 0; $i < $maxDepth; $i++) {
                                    foreach ($packs as $p) {
                                        if (isset($p['rows'][$i])) {
                                            $ordered[] = $p['rows'][$i];
                                        }
                                    }
                                }
                                return $ordered;
                            };

                            // Assign pooled rows into destination tier divisions left→right, cycling
                            $assignToDestDivs = function ($destTier, array $rows): array {
                                $destDivs = collect($destTier['divisions'])->sortBy('index')->values()->all();
                                if (empty($destDivs)) return [];
                                $assigned = [];
                                $d = count($destDivs);
                                foreach ($rows as $k => $row) {
                                    $destDiv = $destDivs[$k % $d];
                                    $assigned[] = ['dest_div' => $destDiv, 'row' => $row];
                                }
                                return $assigned;
                            };

                            $overall = [];
                            $overallRank = 1;
                            $push = function ($tier, $destDivision, $row) use (&$overall, &$overallRank) {
                                $overall[] = [
                                    'overall_rank'   => $overallRank++,
                                    'tier_id'        => data_get($tier, 'id'),
                                    'tier_name'      => data_get($tier, 'name'),
                                    'tier_index'     => (int) data_get($tier, 'index'),

                                    'division_id'    => data_get($destDivision, 'id'),
                                    'division_index' => (int) data_get($destDivision, 'index'),
                                    'division_letter'=> data_get($destDivision, 'division_letter'),
                                    'division_name'  => data_get($destDivision, 'name'),

                                    'division_rank'  => (int) data_get($row, 'rank'),
                                    'contestant_id'  => data_get($row, 'contestant_id'),
                                    'player_id'      => data_get($row, 'player_id'),
                                    'full_name'      => data_get($row, 'full_name'),

                                    // carry stats + trashed flag through
                                    'played'         => (int) data_get($row, 'played', 0),
                                    'won'            => (int) data_get($row, 'won', 0),
                                    'drawn'          => (int) data_get($row, 'drawn', 0),
                                    'lost'           => (int) data_get($row, 'lost', 0),
                                    'for'            => (int) data_get($row, 'for', 0),
                                    'against'        => (int) data_get($row, 'against', 0),
                                    'diff'           => (int) data_get($row, 'diff', 0),
                                    'points'         => (int) data_get($row, 'points', 0),
                                    'seed'           => (int) data_get($row, 'seed', 0),
                                    'trashed'        => (bool) data_get($row, 'trashed', false),
                                ];
                            };

                            // Top -> bottom tiers
                            foreach ($tiers as $tier) {
                                $idx = (int) $tier['index'];
                                $hasUpper = $idx > $tierMin;
                                $hasLower = $idx < $tierMax;

                                $upper = $hasUpper ? $tiersByIndex->get($idx - 1) : null;
                                $lower = $hasLower ? $tiersByIndex->get($idx + 1) : null;

                                // A) Incoming relegations from the tier above (pool across all divisions)
                                $relegRowsPooled = [];
                                if ($upper) {
                                    $relegRowsPooled = $poolTierRows($upper, function ($upperDiv, array $rows) {
                                        $n = count($rows);
                                        $r = max(0, (int) data_get($upperDiv, 'relegate_count', 0));
                                        $start = max(0, $n - $r);
                                        return array_slice($rows, $start); // bottom r
                                    });
                                }
                                $relegAssigned = $assignToDestDivs($tier, $relegRowsPooled);

                                // B) Stayers in THIS tier (pool middles across all divisions)
                                $stayersPooled = $poolTierRows($tier, function ($div, array $rows) use ($upper, $lower) {
                                    $n = count($rows);
                                    $p = $upper ? max(0, (int) data_get($div, 'promote_count', 0)) : 0;
                                    $r = $lower ? max(0, (int) data_get($div, 'relegate_count', 0)) : 0;
                                    $start   = min($p, $n);
                                    $endExcl = max($start, $n - $r);
                                    return array_slice($rows, $start, max(0, $endExcl - $start));
                                });
                                $stayersAssigned = $assignToDestDivs($tier, $stayersPooled);

                                // C) Incoming promotions from the tier below (pool across all divisions)
                                $promRowsPooled = [];
                                if ($lower) {
                                    $promRowsPooled = $poolTierRows($lower, function ($lowerDiv, array $rows) {
                                        $p = max(0, (int) data_get($lowerDiv, 'promote_count', 0));
                                        return array_slice($rows, 0, min(count($rows), $p)); // top p
                                    });
                                }
                                $promAssigned = $assignToDestDivs($tier, $promRowsPooled);

                                // Emit: relegations → stayers → promotions
                                foreach ($relegAssigned as $x) $push($tier, $x['dest_div'], $x['row']);
                                foreach ($stayersAssigned as $x) $push($tier, $x['dest_div'], $x['row']);
                                foreach ($promAssigned as $x)   $push($tier, $x['dest_div'], $x['row']);
                            }

                            foreach ($overall as $contestant) {
                                $session->contestants()
                                    ->where('player_id', $contestant['player_id'])
                                    ->update([
                                        'overall_rank' => $contestant['overall_rank'],
                                        'division_rank' => $contestant['division_rank'],
                                    ]);
                            }

                            $session->update([
                                'processed_at' => now(),
                            ]);

                        });
                    });
                });
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
