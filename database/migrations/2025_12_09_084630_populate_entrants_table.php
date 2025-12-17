<?php

use App\Models\League;
use App\Models\Member;
use App\Models\Entrant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected function buildStructure($session)
    {
        $this->noTopTierPromotionNoBottomTierRelegation($session);

        // Get all tiers with their divisions and contestants, eager loaded
        $tiers = $session->tiers()
            ->orderBy('index')
            ->with([
                'divisions.contestants.member' => fn ($q) => $q->withTrashed(),
            ])
            ->get();

        // For each tier, find the max contestant count among its divisions
        $maxContestantsPerTier = $tiers->mapWithKeys(function ($tier) {
            return [$tier->id => $tier->divisions->max('contestant_count') ?? 0];
        });

        $structure = [];
        $globalRank = 1;

        foreach ($tiers as $tier) {
            $divisions = $tier->divisions;
            $divisionCount = $divisions->count();
            $isTierMoreThanOneDivision = $divisionCount > 1;
            $maxContestantCount = $maxContestantsPerTier[$tier->id];

            // Prepare divisions for this tier
            $divisionsArray = [];
            for ($divisionIndex = 0; $divisionIndex < $divisionCount; $divisionIndex++) {
            $division = $divisions[$divisionIndex];
            // Key contestants by index for fast lookup
            $contestants_by_index = $division->contestants->keyBy('index');
            $divisionsArray[] = [
                'id' => $division->id,
                'name' => $tier->name . ($isTierMoreThanOneDivision ? '(' . chr(97 + $divisionIndex) . ')' : ''),
                'contestant_count' => $division->contestant_count,
                'promote_count' => $division->promote_count,
                'relegate_count' => $division->relegate_count,
                'contestants_by_index' => $contestants_by_index,
                'contestants' => [], // Always initialize contestants array
            ];
            }

            // Build contestants row-wise (left to right across divisions), assign ranks to all slots
            for ($row = 0; $row < $maxContestantCount; $row++) {
                for ($divisionIndex = 0; $divisionIndex < $divisionCount; $divisionIndex++) {
                    $division = $divisionsArray[$divisionIndex];
                    // Only create a slot if within contestant_count
                    if ($row < $division['contestant_count']) {
                        $contestant = $division['contestants_by_index']->get($row);
                        if ($contestant) {
                            $member = $contestant->member;
                            $content = [
                                'id' => $contestant->id,
                                'index' => $contestant->index,
                                'member_id' => $member->id,
                                'content' => $member->full_name,
                                'deleted_at' => $member->deleted_at,
                                'rank' => $globalRank,
                            ];
                        } else {
                            $content = [
                                'content' => null,
                                'rank' => $globalRank,
                            ];
                        }
                        // Ensure the contestants array is always filled up to contestant_count
                        $divisionsArray[$divisionIndex]['contestants'][$row] = $content;
                        if (!isset($divisionsArray[$divisionIndex]['contestants'])) {
                            $divisionsArray[$divisionIndex]['contestants'] = [];
                        }
                        $globalRank++;
                    }
                }
            }

            // Fill up empty contestant slots if contestant_count > maxContestantCount
            foreach ($divisionsArray as &$divisionArr) {
                $needed = $divisionArr['contestant_count'];
                $currentCount = isset($divisionArr['contestants']) ? count($divisionArr['contestants']) : 0;
                for ($i = $currentCount; $i < $needed; $i++) {
                    $divisionArr['contestants'][$i] = [
                        'content' => null,
                        'rank' => $globalRank,
                    ];
                    $globalRank++;
                }

                // NEW: count non-empty slots (i.e., slots with a member_id)
                $filled = 0;
                if (!empty($divisionArr['contestants']) && is_array($divisionArr['contestants'])) {
                    foreach ($divisionArr['contestants'] as $slot) {
                        if (is_array($slot) && array_key_exists('member_id', $slot) && $slot['member_id'] !== null && $slot['member_id'] !== '') {
                            $filled++;
                        }
                    }
                }
                $divisionArr['filled_count'] = $filled; // <-- available as $structure[$tierIndex]['divisions'][$divisionIndex]['filled_count']
            }
            unset($divisionArr);

            // Remove helper key
            foreach ($divisionsArray as &$div) {
                unset($div['contestants_by_index']);
            }
            unset($div);

            unset($divisionArr);

            // Remove helper key
            foreach ($divisionsArray as &$div) {
                unset($div['contestants_by_index']);
            }

            $structure[] = [
                'id' => $tier->id,
                'index' => $tier->index,
                'name' => $tier->name,
                'old_name' => $tier->name,
                'divisions' => $divisionsArray,
            ];
        }

        return $this->attachSeedsToStructure($structure, $session);
    }

    public function noTopTierPromotionNoBottomTierRelegation($session)
    {
        // Top tier (smallest index)
        $topTier = $session->tiers()->orderBy('index', 'asc')->first();
        if ($topTier) {
            $topTier->divisions()->where('promote_count', '!=', 0)->update(['promote_count' => 0]);
        }

        // Bottom tier (largest index)
        $bottomTier = $session->tiers()->orderBy('index', 'desc')->first();
        if ($bottomTier) {
            $bottomTier->divisions()->where('relegate_count', '!=', 0)->update(['relegate_count' => 0]);
        }
    }

    protected function attachSeedsToStructure(array $structure, $session): array
    {
        $lookup = $this->seedLookup($session);

        foreach ($structure as &$tier) {
            if (!isset($tier['divisions']) || !is_array($tier['divisions'])) continue;

            foreach ($tier['divisions'] as &$division) {
                if (!isset($division['contestants']) || !is_array($division['contestants'])) continue;

                foreach ($division['contestants'] as &$slot) {
                    $mid = is_array($slot) ? ($slot['member_id'] ?? null) : null;

                    if ($mid !== null && $mid !== '') {
                        $mid = (int) $mid;
                        $slot['entrant_id'] = $lookup[$mid]['entrant_id'] ?? null;
                        $slot['seed']          = $lookup[$mid]['seed'] ?? null;
                    } else {
                        // keep keys present for a predictable shape
                        $slot['entrant_id'] = null;
                        $slot['seed']          = null;
                    }
                }
                unset($slot);
            }
            unset($division);
        }
        unset($tier);

        return $structure;
    }

    protected function seedLookup($session): array
    {
        return $session->entrants()
            ->orderBy('index')
            ->get(['id', 'member_id', 'index'])
            ->map(fn ($c) => [
                'member_id'     => (int) $c->member_id,
                'entrant_id' => (int) $c->id,
                'seed'          => (int) $c->index + 1, // 1-based seed position
            ])
            ->keyBy('member_id')
            ->map(fn ($row) => ['entrant_id' => $row['entrant_id'], 'seed' => $row['seed']])
            ->all();
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            League::withTrashed()->chunkById(50, function (Collection $leagues) {
                $leagues->each(function ($league) {
                    $league->sessions()->orderBy('id')->chunkById(50, function (Collection $sessions) {
                        $sessions->each(function ($session) {

                            $structure = $this->buildStructure($session);
                            // 1) Compute max rows per tier
                            $maxRowsPerTier = [];
                            foreach ($structure as $tierIndex => $tier) {
                                $max = 0;
                                foreach ($tier['divisions'] as $division) {
                                    $max = max($max, (int) $division['contestant_count']);
                                }
                                $maxRowsPerTier[$tierIndex] = $max;
                            }

                            // 2) Gather member IDs in the exact row-wise order
                            $orderedMemberIds = [];
                            $seen = [];
                            foreach ($structure as $tierIndex => $tier) {
                                $maxRows = $maxRowsPerTier[$tierIndex];
                                for ($row = 0; $row < $maxRows; $row++) {
                                    foreach ($tier['divisions'] as $division) {
                                        if ($row < (int) $division['contestant_count']) {
                                            $slot = $division['contestants'][$row] ?? null;
                                            $memberId = $slot['member_id'] ?? null;

                                            if ($memberId && !isset($seen[$memberId])) {
                                                $orderedMemberIds[] = (int) $memberId;
                                                $seen[$memberId] = true;
                                            }
                                        }
                                    }
                                }
                            }

                            Entrant::where('league_session_id', $session->id)->delete();

                            if (empty($orderedMemberIds)) {
                                return;
                            }

                            $now = now();
                            $payload = [];
                            foreach ($orderedMemberIds as $i => $memberId) {
                                $payload[] = [
                                    'league_session_id' => $session->id,
                                    'member_id'         => $memberId,
                                    'player_id'         => Member::withTrashed()->find($memberId)->player_id,
                                    'index'             => $i,   // 0-based
                                    'created_at'        => $now,
                                    'updated_at'        => $now,
                                ];
                            }

                            Entrant::insert($payload);

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
        //
    }
};
