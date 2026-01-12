
<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Tier;
use App\Models\League;
use App\Models\Session;
use App\Models\Division;
use App\Models\Contestant;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public int $selectedEntrantToAdd;

    public array $structure = [];

    public Collection $availableMember;

    public function mount()
    {
        $this->structure = $this->buildStructure();
    }

    protected function buildStructure()
    {
        $this->noTopTierPromotionNoBottomTierRelegation();

        // Get all tiers with their divisions and contestants, eager loaded
        $tiers = $this->session->tiers()
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
                        $content = [
                            'contestant' => null,
                            'rank' => $globalRank,
                        ];
                        if ($contestant) {
                            $content['contestant'] = $contestant->load('member');
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

                // NEW: count non-empty slots (i.e., slots with a id)
                $filled = 0;
                if (!empty($divisionArr['contestants']) && is_array($divisionArr['contestants'])) {
                    foreach ($divisionArr['contestants'] as $slot) {
                        if (is_array($slot) && array_key_exists('id', $slot) && $slot['id'] !== null && $slot['id'] !== '') {
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

        return $this->attachSeedsToStructure($structure);
    }

    public function scaffoldFromTemplate()
    {
        DB::transaction(function () {
            $this->session->tiers()->forceDelete();

            $tiers = $this->league->template;

            foreach ($tiers as $tierIndex => $tier) {
                $tierModal = $this->session->tiers()->create([
                    'name' => $tier['name'],
                    'index' => $tierIndex,
                ]);

                foreach ($tier['divisions'] as $divisionIndex => $division) {
                    $tierModal->divisions()->create([
                        'league_session_id' => $this->session->id,
                        'index' => $divisionIndex,
                        'contestant_count' => $division['contestant_count'],
                        'promote_count' => $division['promote_count'],
                        'relegate_count' => $division['relegate_count'],
                    ]);
                }
            }

            $this->populate();
        });

        Flux::modals()->close('scaffold-from-template');

        Flux::toast(
            variant: 'success',
            text: 'Structure scaffolded.'
        );
    }

    /**
     * member_id => ['entrant' => int, 'seed' => int]
     */
    protected function seedLookup(): array
    {
        return $this->session->entrants()
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

    public function duplicateTier($tierId)
    {
        DB::transaction(function () use ($tierId) {
            // Find the tier to duplicate
            $originalTier = Tier::with(['divisions'])->lockForUpdate()->findOrFail($tierId);

            // Determine the new index (insert after the original)
            $newIndex = $originalTier->index + 1;

            // Shift all tiers after the original up by 1 to make space
            $this->session->tiers()
                ->where('index', '>=', $newIndex)
                ->increment('index');

            // Create the new tier
            $newTier = $this->session->tiers()->create([
                // 'name'  => $originalTier->name,
                'name'  => $originalTier->name,
                'index' => $newIndex,
            ]);

            // Duplicate each division (empty slots)
            foreach ($originalTier->divisions as $division) {
                $newTier->divisions()->create([
                    'league_session_id' => $this->session->id,
                    'index'             => $division->index,
                    'contestant_count'  => $division->contestant_count,
                    'promote_count'     => $division->promote_count,
                    'relegate_count'    => $division->relegate_count,
                ]);
            }

            // Reindex divisions in the new tier to ensure no collisions
            $this->normalizeTierDivisionIndices($newTier);

            // Reindex all tiers to ensure unique, contiguous indices
            $this->normalizeTierIndices();
        });

        Flux::modals()->close('duplicate-tier-'.$tierId);

        // Refresh structure
        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: 'Tier duplicated.'
        );
    }

    public function duplicateDivision($divisionId)
    {
        DB::transaction(function () use ($divisionId) {
            // Find the division to duplicate
            $originalDivision = Division::lockForUpdate()->findOrFail($divisionId);
            $tier = Tier::lockForUpdate()->findOrFail($originalDivision->tier_id);

            // Determine the new index (insert after the original)
            $newIndex = $originalDivision->index + 1;

            // Shift all divisions in the same tier after the original up by 1 to make space
            $tier->divisions()
                ->where('index', '>=', $newIndex)
                ->increment('index');

            // Create the new division
            $tier->divisions()->create([
                'league_session_id' => $this->session->id,
                'index'             => $newIndex,
                'contestant_count'  => $originalDivision->contestant_count,
                'promote_count'     => $originalDivision->promote_count,
                'relegate_count'    => $originalDivision->relegate_count,
            ]);

            // Reindex divisions in this tier to ensure no collisions
            $this->normalizeTierDivisionIndices($tier);
        });

        Flux::modals()->close('duplicate-division-'.$divisionId);

        // Refresh structure
        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: 'Division duplicated.'
        );
    }

    /**
     * Enrich each slot in $structure with 'seed' and 'entrant_id'.
     */
    protected function attachSeedsToStructure(array $structure): array
    {
        $lookup = $this->seedLookup();

        foreach ($structure as &$tier) {
            if (!isset($tier['divisions']) || !is_array($tier['divisions'])) continue;

            foreach ($tier['divisions'] as &$division) {
                if (!isset($division['contestants']) || !is_array($division['contestants'])) continue;

                foreach ($division['contestants'] as &$slot) {
                    $mid = is_array($slot) ? ($slot['contestant']->member_id ?? null) : null;

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


    public function populate()
    {
        DB::transaction(function () {
            $entrants = $this->getEntrants();

            // Gather all slots row-wise across all tiers and divisions (top to bottom, left to right)
            $allSlots = [];
            $maxRowsPerTier = [];
            foreach ($this->structure as $tierIndex => $tier) {
                $maxRows = 0;
                foreach ($tier['divisions'] as $division) {
                $maxRows = max($maxRows, $division['contestant_count']);
                }
                $maxRowsPerTier[$tierIndex] = $maxRows;
            }

            foreach ($this->structure as $tierIndex => $tier) {
                $maxRows = $maxRowsPerTier[$tierIndex];
                for ($row = 0; $row < $maxRows; $row++) {
                    foreach ($tier['divisions'] as $divisionIndex => $division) {
                        if ($row < $division['contestant_count']) {
                        $allSlots[] = [
                            'tier_index' => $tierIndex,
                            'division_index' => $divisionIndex,
                            'slot_index' => $row,
                        ];
                        }
                    }
                }
            }

            // Assign entrants to slots in order
            foreach ($allSlots as $i => $slot) {
                $member = $entrants[$i] ?? null;
                $tierIndex = $slot['tier_index'];
                $divisionIndex = $slot['division_index'];
                $slotIndex = $slot['slot_index'];
                if ($member) {
                    $this->structure[$tierIndex]['divisions'][$divisionIndex]['contestants'][$slotIndex] = [
                        'content' => $member->full_name ?? ($member['first_name'] . ' ' . $member['last_name']),
                        'member_id' => $member->id ?? $member['id'],
                        'rank' => null,
                    ];
                } else {
                    $this->structure[$tierIndex]['divisions'][$divisionIndex]['contestants'][$slotIndex] = [
                        'content' => '-',
                        'member_id' => null,
                        'rank' => null,
                    ];
                }
            }

            // Persist to database
            foreach ($this->structure as $tier) {
                foreach ($tier['divisions'] as $division) {
                    $divisionModel = Division::find($division['id']);

                    // Get current contestants keyed by index
                    $existingContestants = $divisionModel->contestants()->get()->keyBy('index');

                    foreach ($division['contestants'] as $slotIndex => $contestant) {
                        if (isset($contestant['member_id'])) {
                            // Update if exists, else create
                            $existing = $existingContestants->get($slotIndex);
                            if ($existing) {
                                $existing->update([
                                    'member_id' => $contestant['member_id'],
                                    'index' => $slotIndex,
                                ]);
                            } else {
                                $divisionModel->contestants()->create([
                                    'league_session_id' => $divisionModel->league_session_id,
                                    'member_id' => $contestant['member_id'],
                                    'index' => $slotIndex,
                                ]);
                            }
                        } else {
                            // If slot is empty and a contestant exists, remove it
                            if ($existingContestants->has($slotIndex)) {
                                $existingContestants[$slotIndex]->deleteFromDivision();
                            }
                        }
                    }

                    // Remove contestants not present in the new structure or whose member_id changed
                    foreach ($existingContestants as $slotIndex => $existing) {
                        if (
                            !isset($division['contestants'][$slotIndex]['member_id']) ||
                            $division['contestants'][$slotIndex]['member_id'] != $existing->member_id
                        ) {
                            $existing->deleteFromDivision();
                        }
                    }
                }
            }

            $this->structure = $this->buildStructure();
        });

        Flux::modals()->close('populate-entrants');

        Flux::toast(
            variant: 'success',
            text: 'Slots populated.'
        );
    }

    public function unpopulate()
    {
        $tiers = $this->session->tiers()->with('divisions.contestants')->get();

        DB::transaction(function () use ($tiers) {
            // Loop through all tiers and their divisions
            foreach ($tiers as $tier) {
                foreach ($tier->divisions as $division) {
                    $division->contestants()->forceDelete();
                }
            }

            // Rebuild the structure to reflect the cleared contestants
            $this->structure = $this->buildStructure();
        });

        Flux::modals()->close('unpopulate-entrants');

        Flux::toast(
            variant: 'success',
            text: 'Slots unpopulated.'
        );
    }

    #[Computed]
    public function entrantCount(): int
    {
        return $this->session->entrants()->count();
    }

    protected function getEntrants(): array
    {
        return $this->session
            ->entrants()
            ->with([
                'member' => fn ($query) => $query->select('id', 'first_name', 'last_name')
            ])
            ->orderBy('index')
            ->get()
            ->pluck('member')
            ->toArray();
    }

    public function getEntrantsInAlphabeticalOrder()
    {
        return $this->session
            ->entrants()
            ->join('members', 'entrants.member_id', '=', 'members.id')
            ->orderBy('members.last_name')
            ->orderBy('members.first_name')
            ->select('members.id', 'members.club_member_id', 'members.first_name', 'members.last_name', 'members.user_id', 'entrants.index', 'members.deleted_at')
            ->get()
            ->toArray();
    }

    #[Computed]
    public function unallocatedEntrants()
    {
        // Get all member_ids currently assigned in the structure
        $usedMemberIds = [];
        foreach ($this->structure as $tier) {
            foreach ($tier['divisions'] as $division) {
                foreach ($division['contestants'] as $slot) {
                    if ($slot['contestant']) {
                        $usedMemberIds[] = $slot['contestant']->member_id;
                    }
                }
            }
        }

        // Get all available entrants (members eligible for assignment)
        $allEntrants = $this->getEntrantsInAlphabeticalOrder();

        // Filter out those already used in the structure
        $unused = array_filter($allEntrants, function ($member) use ($usedMemberIds) {
            return !in_array($member['id'] ?? $member->id, $usedMemberIds);
        });

        // Return as a collection or array as needed
        return array_values($unused);
    }

    #[Computed]
    public function unallocatedEntrantCount()
    {
        return count($this->unallocatedEntrants);
    }

    public function updateTierName(int $tierIndex)
    {
        $this->validate([
            "structure.$tierIndex.name" => 'required|string|max:20',
        ], [
            "structure.$tierIndex.name.max" => 'Must not be more than 20 characters.'
        ]);

        $tierId = $this->structure[$tierIndex]['id'];
        $name = trim($this->structure[$tierIndex]['name']);

        if ($name === '') {
            return;
        }

        $tier = Tier::find($tierId);

        $tier->update(['name' => $name]);

        $tierNo = $tier->index + 1;

        $this->structure = $this->buildStructure();

        Flux::modals()->close("update-tier-name-{$tierId}");

        Flux::toast(
            variant: 'success',
            text: 'Tier '.($tierIndex + 1). ' name updated.'
        );
    }

    protected function normalizeTierIndices(): void
    {
        // lock all remaining tiers in the session and renumber deterministically
        $tiers = $this->session->tiers()
            ->lockForUpdate()
            ->orderBy('index')
            ->orderBy('id') // stable tiebreak in case of dup indices
            ->get(['id','index']);

        foreach ($tiers as $i => $t) {
            if ($t->index !== $i) {
                $t->update(['index' => $i]);
            }
        }
    }

    public function deleteTier(int $tierId)
    {
        $tierIndex = DB::transaction(function () use ($tierId) {
            // lock the target tier
            $tier     = Tier::lockForUpdate()->findOrFail($tierId);
            $tierIndex = $tier->index;

            // delete (and optionally clean children if not on cascade)
            // $division->results()->forceDelete();
            // $division->contestants()->forceDelete();
            $tier->forceDelete();

            // renumber everything left to 0..n-1 (removes gaps/duplicates)
            $this->normalizeTierIndices();

            return $tierIndex;
        });

        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: 'Tier deleted.'
        );
    }

    protected function normalizeTierDivisionIndices(Tier $tier): void
    {
        // lock all remaining divisions in this tier and renumber deterministically
        $divisions = $tier->divisions()
            ->lockForUpdate()
            ->orderBy('index')
            ->orderBy('id') // stable tiebreak in case of dup indices
            ->get(['id','index']);

        foreach ($divisions as $i => $d) {
            if ($d->index !== $i) {
                $d->update(['index' => $i]);
            }
        }
    }

    public function deleteDivision(int $divisionId, string $divisionName)
    {
        DB::transaction(function () use ($divisionId) {
            // lock the target division and its tier row
            $division = Division::lockForUpdate()->findOrFail($divisionId);
            $tier     = Tier::lockForUpdate()->findOrFail($division->tier_id);

            // delete (and optionally clean children if not on cascade)
            // $division->results()->forceDelete();
            // $division->contestants()->forceDelete();
            $division->forceDelete();

            // renumber everything left to 0..n-1 (removes gaps/duplicates)
            $this->normalizeTierDivisionIndices($tier);
        });

        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: 'Division deleted.'
        );
    }

    public function deleteSlot(int $divisionId, int $contestantIndex, string $divisionName, int $rank)
    {
        $division = Division::find($divisionId);
        $contestant = $division->contestants()->where('index', $contestantIndex)->first();

        DB::transaction(function () use ($division, $contestant, $contestantIndex, $rank, $divisionName) {
            $division->update([
                'contestant_count' => $division->contestant_count - 1,
            ]);

            $division->contestants()
                ->where('index', '>', $contestantIndex)
                ->decrement('index');

            if (! is_null($contestant)) {
                $contestant->deleteFromDivision();
            }

            $this->adjustPromoteRelegateForDivision($division);

            $this->structure = $this->buildStructure();

            Flux::toast(
                variant: 'success',
                text: 'Slot deleted.'
            );
        });
    }

    protected function adjustPromoteRelegateForDivision($division)
    {
        if ($division->contestant_count < $division->promote_count + $division->relegate_count) {
            $isPromoteCountGreaterThanRelegateCount = $division->promote_count > $division->relegate_count;
            $isPromoteCountGreaterThanRelegateCount ?
                $division->decrement('promote_count') :
                $division->decrement('relegate_count');
        }
    }

    public function deleteContestant($contestantId)
    {
        $contestant = Contestant::find($contestantId);

        $contestant->deleteFromDivision();

        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: 'Entrant removed from slot.'
        );
    }

    #[On('member-removed')]
    public function removeEntrant($memberId)
    {
        foreach ($this->session->tiers as $tier) {
            foreach ($tier->divisions as $division) {
                foreach ($division->contestants as $contestant) {
                    if ($contestant->member_id == $memberId) {
                        $contestant->deleteFromDivision();
                    }
                }
            }
        }

        Flux::toast(
            variant: 'success',
            text: 'Entrant removed.'
        );

        $this->structure = $this->buildStructure();
    }

    public function addContestant($divisionId, $contestantIndex)
    {
        $division = Division::find($divisionId);

        $division->contestants()->create([
            'league_session_id' => $division->league_session_id,
            'member_id' => $this->pull('selectedEntrantToAdd'),
            'index' => $contestantIndex,
        ]);

        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: 'Entrant added to slot.'
        );
    }

    public function addSlot($divisionId)
    {
        $division = Division::find($divisionId);

        $division->update([
            'contestant_count' => $division->contestant_count + 1,
        ]);

        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: 'Slot added.'
        );
    }

    public function addTier()
    {
        DB::transaction(function () {
            $tierCount = $this->session->tiers()->count();

            $tierName = '';

            $lastTierIndex = $tierCount - 1;

            if ($tierCount === 0) {
                $tierName =  __('Premier');
            } else {
                $aboveTierName = $this->structure[$lastTierIndex]['name'];

                if (preg_match('/^(.*?)(\d+)$/', $aboveTierName, $matches)) {
                    $text   = trim($matches[1]); // "Division"
                    $number = (int) $matches[2]; // 1
                }

                $text = isset($text) ? $text : __('Division');
                $number = isset($number) ? $number + 1 : 1;

                $tierName = $text.' '.$number;
            }

            $tier = $this->session->tiers()->create([
                'name' => $tierName,
                'index' => $tierCount,
            ]);

            $this->addDivision($tier->id);
        });

        Flux::toast(
            variant: 'success',
            text: 'Tier added.'
        );
    }

    public function addDivision($tierId)
    {
        $tier = Tier::find($tierId);

        $divisionCount = $tier->divisions()->count();

        $tier->divisions()->create([
            'league_session_id' => $this->session->id,
            'index' => $divisionCount,
            'contestant_count' => 5,
            'promote_count' => 0,
            'relegate_count' => 0,
        ]);

        $this->structure = $this->buildStructure();
    }

    #[Computed]
    public function slotCount(): int
    {
        $total = 0;

        foreach ($this->structure as $tier) {
            if (!isset($tier['divisions']) || !is_array($tier['divisions'])) {
                continue; // tolerate edits / malformed partial state
            }

            foreach ($tier['divisions'] as $division) {
                $total += max(0, (int) ($division['contestant_count'] ?? 0));
            }
        }

        return $total;
    }

    #[Computed]
    public function filledSlotCount(): int
    {
        $filled = 0;

        foreach ($this->structure as $tier) {
            if (!isset($tier['divisions']) || !is_array($tier['divisions'])) {
                continue;
            }

            foreach ($tier['divisions'] as $division) {
                if (!isset($division['contestants']) || !is_array($division['contestants'])) {
                    continue;
                }

                foreach ($division['contestants'] as $slot) {
                    if ($slot['contestant']?->member_id) { // why: treat any truthy member_id as filled
                        $filled++;
                    }
                }
            }
        }

        return $filled;
    }

    protected function findStructureSlotByRank(int $rank): ?array
    {
        if ($rank <= 0) {
            return null; // why: ranks are 1-based
        }

        foreach ($this->structure as $tI => $tier) {
            if (!isset($tier['divisions']) || !is_array($tier['divisions'])) {
                continue;
            }

            foreach ($tier['divisions'] as $dI => $division) {
                if (!isset($division['contestants']) || !is_array($division['contestants'])) {
                    continue;
                }

                foreach ($division['contestants'] as $sI => $slot) {
                    $slotRank = is_array($slot) && array_key_exists('rank', $slot) ? (int) $slot['rank'] : null;
                    if ($slotRank === $rank) {
                        return [
                            'tier_index'    => $tI,
                            'division_index'=> $dI,
                            'slot_index'    => $sI,
                            'tierId'       => (int) ($tier['id'] ?? 0),
                            'divisionId'   => (int) ($division['id'] ?? 0),
                            'slot'         => $slot,
                        ];
                    }
                }
            }
        }

        return null; // not found
    }

    public function divisionIdByRank(int $rank): ?int
    {
        $hit = $this->findStructureSlotByRank($rank);
        return $hit && !empty($hit['divisionId']) ? (int) $hit['divisionId'] : null;
    }

    /**
     * Get the Contestant Eloquent model (if present) for a given rank.
     * Returns null when the slot is empty or not found.
     */
    public function contestantByRank(int $rank): ?Contestant
    {
        $hit = $this->findStructureSlotByRank($rank);
        if (!$hit) {
            return null;
        }

        $slot = $hit['slot'];
        $contestantId = is_array($slot) && !empty($slot['id']) ? (int) $slot['id'] : null;
        return $contestantId ? Contestant::find($contestantId) : null;
    }

    public function sortTier(int $id, int $index): void
    {
        $tier = $this->session->tiers()->findOrFail($id);
        $tier->move($index);

        $this->structure = $this->buildStructure();
    }

    public function sortDivision(int $id, int $divisionIndex, int $tierIndex): void
    {
        $division = $this->session->divisions()->findOrFail($id);

        if ($division->tier->index !== $tierIndex) {
            $division->displace();
            $division->tier()->associate($this->session->tiers()->where('index', $tierIndex)->first());
        }

        $division->move($divisionIndex);

        $this->structure = $this->buildStructure();
    }

    public function sortSlot(int $rank, int $targetIndex, int $targetTierIndex, int $targetDivisionIndex): void
    {
        $hit = $this->findStructureSlotByRank($rank);
        if (!$hit) return;

        $sourceDivisionId = (int) $hit['divisionId'];
        $sourceIndex      = (int) $hit['slot_index'];
        $slot             = $hit['slot'] ?? [];
        $movingContestantId = isset($slot['contestant']->id) ? (int) $slot['contestant']->id : null;

        $targetDivisionArr = $this->structure[$targetTierIndex]['divisions'][$targetDivisionIndex] ?? null;
        if (!$targetDivisionArr) return;

        $targetDivisionId = (int) $targetDivisionArr['id'];

        DB::transaction(function () use (
            $sourceDivisionId,
            $sourceIndex,
            $targetDivisionId,
            $targetIndex,
            $movingContestantId
        ) {
            /** @var Division $sourceDivision */
            /** @var Division $targetDivision */
            $sourceDivision = Division::lockForUpdate()->findOrFail($sourceDivisionId);
            $targetDivision = Division::lockForUpdate()->findOrFail($targetDivisionId);

            // === Same division: just reorder ===
            if ($sourceDivision->id === $targetDivision->id) {
                $maxIdx = max(0, $sourceDivision->contestant_count - 1);
                $targetIndex = max(0, min($targetIndex, $maxIdx));

                if ($targetIndex === $sourceIndex) {
                    return;
                }

                if ($targetIndex > $sourceIndex) {
                    Contestant::where('division_id', $sourceDivision->id)
                        ->whereBetween('index', [$sourceIndex + 1, $targetIndex])
                        ->decrement('index');
                } else {
                    Contestant::where('division_id', $sourceDivision->id)
                        ->whereBetween('index', [$targetIndex, $sourceIndex - 1])
                        ->increment('index');
                }

                if ($movingContestantId) {
                    Contestant::whereKey($movingContestantId)->update(['index' => $targetIndex]);
                }

                return;
            }

            // === Different division: MOVE THE SLOT ===
            // 1) Shrink source division: shift down rows after the removed slot, then decrement count.
            Contestant::where('division_id', $sourceDivision->id)
                ->where('index', '>', $sourceIndex)
                ->decrement('index');

            $sourceDivision->update([
                'contestant_count' => max(0, $sourceDivision->contestant_count - 1),
            ]);

            // 2) Grow target division: clamp targetIndex (allow append), shift up from targetIndex, then increment count.
            $tCount = (int) $targetDivision->contestant_count;
            $targetIndex = max(0, min($targetIndex, $tCount)); // allow append at end

            Contestant::where('division_id', $targetDivision->id)
                ->where('index', '>=', $targetIndex)
                ->increment('index');

            $targetDivision->update([
                'contestant_count' => $tCount + 1,
            ]);

            // 3) If the dragged slot had a contestant, move it over into the new gap.
            if ($movingContestantId) {
                Contestant::whereKey($movingContestantId)->update([
                    'division_id' => $targetDivision->id,
                    'index'       => $targetIndex,
                ]);
            }

            $this->adjustPromoteRelegateForDivision($sourceDivision);
        });

        // Rebuild to refresh UI (names, ranks, placeholders)
        $this->structure = $this->buildStructure();
    }

    #[Computed]
    public function emptySlotCount(): int
    {
        // derive from totals to keep the three values consistent
        $empty = $this->slotCount() - $this->filledSlotCount();
        return $empty > 0 ? $empty : 0;
    }

    #[Computed]
    public function structureIsAlignedWithEntrants(): bool
    {
        // Seed order (member_ids) by entrant index
        $seed = $this->session->entrants()
            ->orderBy('index')
            ->pluck('member_id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        // Row-wise structure sequence (null for empty slots)
        $slots = [];
        foreach ($this->structure as $tier) {
            if (!isset($tier['divisions']) || !is_array($tier['divisions'])) {
                continue;
            }
            $maxRows = 0;
            foreach ($tier['divisions'] as $division) {
                $maxRows = max($maxRows, (int)($division['contestantCount'] ?? $division['contestant_count'] ?? 0));
            }
            for ($r = 0; $r < $maxRows; $r++) {
                foreach ($tier['divisions'] as $division) {
                    $count = (int)($division['contestantCount'] ?? $division['contestant_count'] ?? 0);
                    if ($r >= $count) continue;
                    $slot = $division['contestants'][$r] ?? null;
                    $mid  = is_array($slot) ? ($slot['member_id'] ?? null) : null;
                    $slots[] = ($mid === null || $mid === '') ? null : (int)$mid;
                }
            }
        }

        if ($slots === []) {
            return true;
        }

        $N = count($seed);
        $M = count($slots);

        // 1) For the first min(M,N) positions, every filled slot must equal the seed at that position.
        $L = min($M, $N);
        for ($i = 0; $i < $L; $i++) {
            $expected = (int)$seed[$i];
            if ($slots[$i] !== null && $slots[$i] !== $expected) {
                return false;
            }
            // null here is allowed and consumes a seed position
        }

        // 2) If structure has extra slots beyond the number of seeds,
        //    they must be empty.
        for ($i = $N; $i < $M; $i++) {
            if ($slots[$i] !== null) {
                return false; // non-empty after seeds are exhausted
            }
        }

        return true;
    }

    #[Computed]
    public function canPopulate()
    {
        return $this->slotCount() > 0 && $this->entrantCount > 0;
    }

    public function promote($inc, $divisionId)
    {
        $division = Division::findOrFail($divisionId);

        $promote_count = $division->promote_count + $inc;
        $relegate_count = $division->contestant_count - $division->promote_count;

        DB::transaction(function () use ($division, $promote_count, $relegate_count) {
            $division->update([
                'promote_count' => $promote_count,
            ]);

            if ($division->relegate_count > $relegate_count) {
                $division->update([
                    'relegate_count' => $relegate_count,
                ]);
            }
        });

        $this->structure = $this->buildStructure();
    }

    public function noTopTierPromotionNoBottomTierRelegation()
    {
        // Top tier (smallest index)
        $topTier = $this->session->tiers()->orderBy('index', 'asc')->first();
        if ($topTier) {
            $topTier->divisions()->where('promote_count', '!=', 0)->update(['promote_count' => 0]);
        }

        // Bottom tier (largest index)
        $bottomTier = $this->session->tiers()->orderBy('index', 'desc')->first();
        if ($bottomTier) {
            $bottomTier->divisions()->where('relegate_count', '!=', 0)->update(['relegate_count' => 0]);
        }
    }

    #[Renderless]
    public function updatedStructure($value, $key)
    {
        // $key shape: "<tierIndex>.divisions.<divisionIndex>.<column>"
        $parts         = explode('.', (string) $key);
        if ($parts[1] === 'name') {
            return;
        }
        $tierIndex     = (int) ($parts[0] ?? -1);
        $divisionIndex = (int) ($parts[2] ?? -1);
        $columnKey     = $parts[3] ?? null;

        // map camelCase -> DB columns if needed
        $colMap = [
            'contestant_count' => 'contestant_count',
            'promoteCount'    => 'promote_count',
            'relegateCount'   => 'relegate_count',
        ];
        $dbCol = $colMap[$columnKey] ?? $columnKey;

        // numeric safety for counts
        if (in_array($dbCol, ['contestant_count','promote_count','relegate_count'], true)) {
            $value = max(0, (int) $value);
        }

        DB::transaction(function () use ($tierIndex, $divisionIndex, $dbCol, $value) {
            // 1) Save the edited division column (if present)
            if ($dbCol) {
                $division = $this->session
                    ->tiers()->where('index', $tierIndex)->firstOrFail()
                    ->divisions()->where('index', $divisionIndex)->firstOrFail();

                $division->update([$dbCol => $value]);
            }

            $this->noTopTierPromotionNoBottomTierRelegation();
        });

        // Refresh the Livewire structure snapshot
        $this->structure = $this->buildStructure();

        Flux::toast(
            variant: 'success',
            text: ($dbCol === 'promote_count' ? 'Promote' : 'Relegate').' count updated.'
        );
    }

    #[Computed]
    public function canBuildTables()
    {
        if (
            $this->unallocatedEntrantCount !== 0 ||
            $this->entrantCount <= 0 ||
            $this->slotCount <= 0 ||
            $this->emptySlotCount !== 0
        ) {
            return false;
        }

        // new: every tier must have at least one division,
        // and every division must be fully filled (no empty slots)
        foreach ($this->structure as $tier) {
            if (empty($tier['divisions']) || !is_array($tier['divisions'])) {
                return false;
            }

            foreach ($tier['divisions'] as $division) {
                // tolerate either key style
                $needed = (int) ($division['contestant_count'] ?? $division['contestantCount'] ?? 0);
                if ($needed <= 0) {
                    return false;
                }

                $filled = 0;
                if (!empty($division['contestants']) && is_array($division['contestants'])) {
                    foreach ($division['contestants'] as $slot) {
                        $mid = $slot['contestant'] ? ($slot['contestant']->member_id ?? null) : null;
                        if ($mid !== null && $mid !== '') {
                            $filled++;
                        }
                    }
                }

                if ($filled < $needed) {
                    return false; // has empty slots
                }
            }
        }

        return true;
    }

    public function pruneStructure()
    {
        $removedSlots      = 0;   // slots removed by compacting (contestant_count shrinkage)
        $reindexedPlayers  = 0;   // contestants whose index changed
        $removedDivisions  = 0;
        $removedTiers      = 0;
        $reindexedDivs     = 0;
        $reindexedTiers    = 0;

        DB::transaction(function () use (
            &$removedSlots, &$reindexedPlayers, &$removedDivisions, &$removedTiers, &$reindexedDivs, &$reindexedTiers
        ) {
            // Lock tiers in stable order
            $tiers = $this->session->tiers()
                ->lockForUpdate()
                ->orderBy('index')
                ->get();

            foreach ($tiers as $tier) {
                // Lock divisions for this tier in index order
                $divisions = $tier->divisions()
                    ->lockForUpdate()
                    ->orderBy('index')
                    ->get();

                foreach ($divisions as $division) {
                    // 1) Compact contestants: reindex contiguous [0..n-1]
                    $originalCount = (int) $division->contestant_count;

                    $contestants = $division->contestants()
                        ->lockForUpdate()
                        ->orderBy('index')
                        ->get();

                    $n = $contestants->count();

                    foreach ($contestants as $i => $c) {
                        if ((int) $c->index !== $i) {
                            $c->updateQuietly(['index' => $i]);
                            $reindexedPlayers++;
                        }
                    }

                    // 2) Adjust contestant_count to actual count; track removed "empty slots"
                    if ($originalCount > $n) {
                        $removedSlots += ($originalCount - $n);
                    }

                    // If there are contestants, persist the compacted count; else delete division
                    if ($n > 0) {
                        if ($originalCount !== $n) {
                            $division->updateQuietly(['contestant_count' => $n]);
                        }
                    } else {
                        $division->forceDelete(); // use ->delete() for soft-deletes
                        $removedDivisions++;
                    }
                }

                // 3) Reindex remaining divisions in this tier (bump to avoid collisions)
                $tier->divisions()->update(['index' => DB::raw('`index` + 1000000')]);

                $remainingDivisions = $tier->divisions()
                    ->lockForUpdate()
                    ->orderBy('index')
                    ->get();

                foreach ($remainingDivisions as $i => $d) {
                    if ((int) $d->index !== $i) {
                        $d->updateQuietly(['index' => $i]);
                        $reindexedDivs++;
                    }
                }
            }

            // Refresh tiers with division counts
            $tiers = $this->session->tiers()
                ->withCount('divisions')
                ->lockForUpdate()
                ->orderBy('index')
                ->get();

            // 4) Delete tiers without divisions
            foreach ($tiers as $tier) {
                if ((int) $tier->divisions_count === 0) {
                    $tier->forceDelete(); // use ->delete() for soft-deletes
                    $removedTiers++;
                }
            }

            // 5) Reindex remaining tiers (bump to avoid unique collisions)
            $this->session->tiers()->update(['index' => DB::raw('`index` + 1000000')]);

            $remainingTiers = $this->session->tiers()
                ->lockForUpdate()
                ->orderBy('index')
                ->get();

            foreach ($remainingTiers as $i => $t) {
                if ((int) $t->index !== $i) {
                    $t->updateQuietly(['index' => $i]);
                    $reindexedTiers++;
                }
            }
        });

        // Rebuild UI/state
        $this->structure = $this->buildStructure();

        Flux::modals()->close('prune-structure');

        Flux::toast(
            variant: 'success',
            text: 'Structure pruned.'
        );
    }

    public function deleteStructure()
    {
        $this->session->tiers()->forceDelete();

        $this->structure = $this->buildStructure();

        Flux::modals()->close('delete-structure');

        Flux::toast(
            variant: 'success',
            text: 'Structure deleted.'
        );
    }
}; ?>

<div class="relative space-y-6">

    <flux:card class="grid grid-cols-3 gap-4 !bg-stone-50">
        <div class="col-span-3 grid grid-cols-2 sm:col-span-2 gap-4">
            <div class="flex flex-col items-center">
                <div class="flex items-center gap-1">
                    <flux:text>Allocated Entrants</flux:text>
                    <flux:tooltip class="hidden sm:block">
                        <flux:button
                            icon="information-circle"
                            size="xs"
                            variant="subtle"
                        />
                        <flux:tooltip.content class="tooltip">All entrants must be allocated.</flux:tooltip.content>
                    </flux:tooltip>
                </div>
                <flux:heading size="xl" variant="bold" class="flex items-center gap-1">
                    @if ($this->entrantCount > 0)
                        <span>{{ $this->entrantCount - $this->unallocatedEntrantCount }}</span>
                        <span>/</span>
                        <span>{{ $this->entrantCount }}</span>
                    @else
                        -
                    @endif
                </flux:heading>
            </div>
            <div class="flex flex-col items-center">
                <div class="flex items-center gap-1">
                    <flux:text>Filled Slots</flux:text>
                    <flux:tooltip class="hidden sm:block">
                        <flux:button
                            icon="information-circle"
                            size="xs"
                            variant="subtle"
                        />
                        <flux:tooltip.content class="tooltip" class="tooltip">All slots must be filled.</flux:tooltip.content>
                    </flux:tooltip>
                </div>
                <flux:heading size="xl" variant="bold" class="flex items-center gap-1">
                    @if ($this->slotCount > 0)
                        <span>{{ $this->filledSlotCount }}</span>
                        <span>/</span>
                        <span>{{ $this->slotCount }}</span>
                    @else
                        -
                    @endif
                </flux:heading>
            </div>
        </div>
        <div class="col-span-3 sm:col-span-1 flex flex-col items-center">
            <div class="flex items-center gap-1">
                <flux:text>Seeds in Correct Slots</flux:text>
                <flux:tooltip class="hidden sm:block">
                    <flux:button
                        icon="information-circle"
                        size="xs"
                        variant="subtle"
                    />
                    <flux:tooltip.content class="tooltip" class="tooltip">Seeds don't need to be in their correct slots.</flux:tooltip.content>
                </flux:tooltip>
            </div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" variant="bold" class="flex items-center gap-1">
                    @if ($this->slotCount === $this->emptySlotCount)
                        -
                    @else
                        @if ($this->structureIsAlignedWithEntrants)
                            {{ __('Yes') }}
                        @else
                            {{ __('No') }}
                        @endif
                    @endif
                </flux:heading>
            </div>
        </div>
    </flux:card>

    @if (count($league->template) > 0 || $session->tiers()->exists())
        <div class="flex items-start justify-between gap-2">
            <div class="sm:flex sm:items-center sm:gap-2 space-y-2 sm:space-y-0">
                @if (count($league->template) > 0)
                    <div>
                        <flux:modal.trigger name="scaffold-from-template">
                            <flux:button
                                icon="blocks"
                                icon:variant="micro"
                                variant="primary"
                            >
                                {{ __('Scaffold') }}
                            </flux:button>
                        </flux:modal.trigger>

                        @teleport('body')
                            <flux:modal name="scaffold-from-template" class="modal">
                                <form wire:submit="scaffoldFromTemplate">
                                    <x-modals.content>
                                        <x-slot:heading>{{ __('Scaffold from Template') }}</x-slot:heading>
                                        <flux:text>{{ __('Are you sure you wish to scaffold a new structure of tiers and divisions from the last saved template?') }}</flux:text>
                                        <flux:text>{{ __('Divisions will contain empty slots for you to populate with entrants.') }}</flux:text>
                                        <x-slot:buttons>
                                            <flux:button type="submit" variant="primary">{{ __('Scaffold') }}</flux:button>
                                        </x-slot:buttons>
                                    </x-modals.content>
                                </form>
                            </flux:modal>
                        @endteleport
                    </div>
                @endif

                @if ($this->slotCount > 0)
                    <div class="flex flex-col items-start">
                        <flux:modal.trigger name="prune-structure">
                            <flux:button
                                icon="scissors"
                                icon:variant="outline"
                            >
                                {{ __('Prune') }}
                            </flux:button>
                        </flux:modal.trigger>

                        @teleport('body')
                            <flux:modal name="prune-structure" class="modal">
                                <form wire:submit="pruneStructure">
                                    <x-modals.content>
                                        <x-slot:heading>{{ __('Prune Structure') }}</x-slot:heading>
                                        <flux:text>{{ __('This action will remove all empty tiers, divisions and slots.') }}</flux:text>
                                        <flux:text>{{ __('Are you sure you wish to prune the structure?') }}</flux:text>
                                        <x-slot:buttons>
                                            <flux:button type="submit" variant="primary">{{ __('Prune') }}</flux:button>
                                        </x-slot:buttons>
                                    </x-modals.content>
                                </form>
                            </flux:modal>
                        @endteleport
                    </div>
                @endif
            </div>

            <div class="sm:flex sm:items-center sm:gap-2 space-y-2 sm:space-y-0">
                @if ($this->canPopulate)
                    <div class="flex flex-col items-end">
                        <flux:modal.trigger name="populate-entrants">
                            <flux:button
                                icon="numbered-list"
                                icon:variant="mini"
                                variant="primary"
                            >
                                {{ __('Populate') }}
                            </flux:button>
                        </flux:modal.trigger>

                        @teleport('body')
                            <flux:modal name="populate-entrants" class="modal">
                                <form wire:submit="populate">
                                    <x-modals.content>
                                        <x-slot:heading>{{ __('Populate in Seed Order') }}</x-slot:heading>
                                        <flux:text>{{ __('Are you sure you wish to populate the structure with the entrants in seed order?') }}</flux:text>
                                        <x-slot:buttons>
                                            <flux:button type="submit" variant="primary">{{ __('Populate') }}</flux:button>
                                        </x-slot:buttons>
                                    </x-modals.content>
                                </form>
                            </flux:modal>
                        @endteleport
                    </div>

                    <div class="flex flex-col items-end">
                        <flux:modal.trigger name="unpopulate-entrants">
                            <flux:button
                                icon="eraser"
                                icon:variant="outline"
                            >
                                {{ __('Unpopulate') }}
                            </flux:button>
                        </flux:modal.trigger>

                        @teleport('body')
                            <flux:modal name="unpopulate-entrants" class="modal">
                                <form wire:submit="unpopulate">
                                    <x-modals.content>
                                        <x-slot:heading>{{ __('Unpopulate Structure') }}</x-slot:heading>
                                        <flux:text>{{ __('Are you sure you wish to remove all entrants from the structure leaving empty slots?') }}</flux:text>
                                        <x-slot:buttons>
                                            <flux:button type="submit" variant="danger">{{ __('Unpopulate') }}</flux:button>
                                        </x-slot:buttons>
                                    </x-modals.content>
                                </form>
                            </flux:modal>
                        @endteleport
                    </div>
                @endif

                @if ($session->tiers()->exists())
                    <div class="flex flex-col items-end">
                        <flux:modal.trigger name="delete-structure">
                            <flux:tooltip>
                                <flux:button
                                    icon="trash"
                                    icon:variant="outline"
                                    variant="subtle"
                                />
                                <flux:tooltip.content class="tooltip">Delete Structure</flux:tooltip.content>
                            </flux:tooltip>
                        </flux:modal.trigger>

                        @teleport('body')
                            <flux:modal name="delete-structure" class="modal">
                                <form wire:submit="deleteStructure">
                                    <x-modals.content>
                                        <x-slot:heading>{{ __('Delete Structure') }}</x-slot:heading>
                                        <flux:text>{{ __('Are you sure you wish to permanently delete this structure') }}.</flux:text>
                                        <x-slot:buttons>
                                            <flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button>
                                        </x-slot:buttons>
                                    </x-modals.content>
                                </form>
                            </flux:modal>
                        @endteleport
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($this->entrantCount > 0 && $this->slotCount > 0)
        @if($this->canBuildTables)
            <flux:callout
                icon="check-circle"
                variant="success"
                inline
            >
                <flux:callout.heading>You can now proceed and view the tables for this session.</flux:callout.heading>
                <flux:callout.text><span class="font-medium">Please note:</span> A template of this structure will be saved. This will help to rapidly scaffold your next session structure.</flux:callout.text>

                <x-slot name="actions" class="!self-center !justify-end">
                    <livewire:components.buttons.build-tables :$club :$league :$session />
                </x-slot>
            </flux:callout>
        @else
            <flux:callout
                icon="exclamation-triangle"
                variant="danger"
                inline
            >
                <flux:callout.text>There must be no empty tiers, divisions or slots and all entrants must be allocated to proceed and view the tables for this session.</flux:callout.text>
            </flux:callout>
        @endif
    @endif

    <div x-sort="$wire.sortTier($item, $position)" class="space-y-6">
        @foreach ($structure as $tierIndex => $tier)
            <div
                x-sort:item="{{ $tier['id'] }}"
                wire:key="{{ $tier['id'] }}"
                class="pt-6 border-t mt-6"
            >
                <div class="space-y-6">
                    <div class="sm:flex sm:items-center sm:justify-between sm:flex-row-reverse gap-2 space-y-6 sm:space-y-0">
                        <div class="flex items-center justify-center gap-2">
                            <flux:tooltip>
                                <flux:button
                                    wire:click="duplicateTier({{ $tier['id'] }})"
                                    icon="document-duplicate"
                                    icon:variant="outline"
                                    variant="subtle"
                                    size="sm"
                                />
                                <flux:tooltip.content class="tooltip">Duplicate Tier</flux:tooltip.content>
                            </flux:tooltip>

                            <flux:modal.trigger name="delete-tier-{{ $tier['id'] }}">
                                <flux:tooltip>
                                    <flux:button
                                        icon="trash"
                                        icon:variant="outline"
                                        variant="subtle"
                                        size="sm"
                                    />
                                    <flux:tooltip.content class="tooltip">Delete Tier</flux:tooltip.content>
                                </flux:tooltip>
                            </flux:modal.trigger>

                            @teleport('body')
                                <flux:modal name="delete-tier-{{ $tier['id'] }}" class="modal">
                                    <form wire:submit="deleteTier({{ $tier['id'] }})">
                                        <x-modals.content>
                                            <x-slot:heading>{{ __('Delete Tier') }}</x-slot:heading>
                                            <flux:text>{{ __('Are you sure you wish to permanently delete Tier') }} {{ $tier['index'] + 1 }}: {{ $tier['name'] }}.</flux:text>
                                            <flux:text>{{ __('This action will also permanently delete all of it\'s divisions') }}.</flux:text>
                                            <x-slot:buttons>
                                                <flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button>
                                            </x-slot:buttons>
                                        </x-modals.content>
                                    </form>
                                </flux:modal>
                            @endteleport

                            @if (count($structure[$tierIndex]['divisions']) === 0)
                                <flux:icon.exclamation-triangle variant="mini" class="mt-1 ml-2 text-red-400" />
                            @endif
                        </div>
                        <div class="flex-1 flex items-center gap-2">
                            <flux:button icon="bars-3" variant="subtle" x-sort:handle class="!shrink-0" />
                            <flux:text class="text-base" variant="subtle" class="!shrink-0 !text-base">{{ __('Tier') }} {{ $loop->iteration }}:</flux:text>

                            <div class="flex items-center gap-1 h-10">
                                <flux:heading size="lg">{{ $tier['old_name'] }}</flux:heading>
                                    <flux:button
                                        x-on:click="$js.openModal({{ $tier['index'] }}, '{{ $tier['old_name'] }}');$flux.modal('update-tier-name-{{ $tier['id'] }}').show()"
                                        variant="subtle"
                                        size="sm"
                                        icon="pencil-square"
                                        icon:variant="outline"
                                    />

                                    @teleport('body')
                                        <flux:modal name="update-tier-name-{{ $tier['id'] }}" class="modal">
                                            <form wire:submit="updateTierName({{ $tier['index'] }})">
                                                <x-modals.content>
                                                    <x-slot:heading>{{ __('Edit Tier Name') }}</x-slot:heading>
                                                    <div class="space-y-4">
                                                        <flux:field>
                                                            <flux:label>{{ __('Tier') }} {{ $tier['index'] + 1 }}</flux:label>
                                                            <flux:input
                                                                x-ref="error-input"
                                                                wire:model="structure.{{ $tier['index'] }}.name"
                                                                placeholder="e.g. Premier"
                                                                required
                                                                autofocus
                                                            />
                                                            <flux:error
                                                                x-ref="error"
                                                                name="structure.{{ $tier['index'] }}.name"
                                                            />
                                                        </flux:field>
                                                    </div>
                                                    <x-slot:buttons>
                                                        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                                                    </x-slot:buttons>
                                                </x-modals.content>
                                            </form>
                                        </flux:modal>
                                    @endteleport
                            </div>
                        </div>
                    </div>

                    <div
                        x-sort:group="divisions"
                        x-sort="$wire.sortDivision($item, $position, {{ $tierIndex }})"
                        class="flex flex-col items-center md:items-start md:flex-row md:justify-center md:flex-wrap md:gap-6 space-y-6 md:space-y-0"
                    >
                        <!-- Divisions -->
                        @foreach ($tier['divisions'] as $divisionIndex => $division)
                            <flux:card
                                x-sort:item="{{ $division['id'] }}"
                                @class([
                                    '!bg-stone-50 space-y-5 w-full sm:w-108'
                                ])
                                wire:key="{{ $division['id'] }}"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <flux:button size="sm" icon="bars-3" variant="subtle" class="!shrink-0" x-sort:handle />
                                        <flux:heading variant="strong" size="lg">
                                            {{ $division['name'] }}
                                        </flux:heading>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <flux:tooltip>
                                            <flux:button
                                                wire:click="duplicateDivision({{ $division['id'] }})"
                                                icon="document-duplicate"
                                                icon:variant="outline"
                                                variant="subtle"
                                                size="sm"
                                            />
                                            <flux:tooltip.content class="tooltip">Duplicate Division</flux:tooltip.content>
                                        </flux:tooltip>

                                        <flux:modal.trigger name="delete-division-{{ $division['id'] }}">
                                            <flux:tooltip>
                                                <flux:button
                                                    icon="trash"
                                                    icon:variant="outline"
                                                    variant="subtle"
                                                    size="sm"
                                                />
                                                <flux:tooltip.content class="tooltip">Delete Division</flux:tooltip.content>
                                            </flux:tooltip>
                                        </flux:modal.trigger>

                                        @teleport('body')
                                            <flux:modal name="delete-division-{{ $division['id'] }}" class="modal">
                                                <form wire:submit="deleteDivision({{ $division['id'] }}, '{{ $division['name'] }}')">
                                                    <x-modals.content>
                                                        <x-slot:heading>{{ __('Delete Division') }}</x-slot:heading>
                                                        <flux:text>{{ __('Are you sure you wish to permanently delete') }} {{ $division['name']}}.</flux:text>
                                                        <x-slot:buttons>
                                                            <flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button>
                                                        </x-slot:buttons>
                                                    </x-modals.content>
                                                </form>
                                            </flux:modal>
                                        @endteleport

                                        @if ($structure[$tierIndex]['divisions'][$divisionIndex]['filled_count'] === 0 && $structure[$tierIndex]['divisions'][$divisionIndex]['contestant_count'] === 0)
                                            <flux:icon.exclamation-triangle variant="mini" class="mt-0.75 ml-2 text-red-400" />
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    @if ($division['contestant_count'] > 0)
                                        <div class="space-y-6">
                                            <div class="flex items-center justify-center gap-4">
                                                @if ($tierIndex > 0)
                                                    @php
                                                        $showPromoteWarning = (int) $structure[$tierIndex]['divisions'][$divisionIndex]['promote_count'] === 0;
                                                    @endphp
                                                    <div class="flex flex-col items-end">
                                                        <div class="flex items-center gap-1">
                                                            <flux:icon.arrow-up variant="mini" class="size-5 text-green-600" />
                                                            <flux:select
                                                                wire:model.live="structure.{{ $tierIndex }}.divisions.{{ $divisionIndex }}.promote_count"
                                                                @class([
                                                                    '!border-amber-300 !bg-amber-50' => $showPromoteWarning
                                                                ])
                                                            >
                                                                @foreach (range(0, $division['contestant_count'] - $division['relegate_count']) as $i)
                                                                    <flux:select.option>{{ $i }}</flux:select.option>
                                                                @endforeach
                                                            </flux:select>
                                                            @if ($showPromoteWarning)
                                                                <flux:icon.exclamation-circle variant="mini" class="text-amber-500" />
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.arrow-up variant="mini" class="size-5 text-green-600" />
                                                        <flux:select
                                                            x-bind:disabled="true"
                                                            class="cursor-not-allowed !bg-stone-50"
                                                        >
                                                            <flux:select.option>0</flux:select.option>
                                                        </flux:select>
                                                    </div>
                                                @endif

                                                @if ($tierIndex < $loop->parent->count - 1)
                                                    @php
                                                        $showRelegateWarning = (int) $structure[$tierIndex]['divisions'][$divisionIndex]['relegate_count'] === 0;
                                                    @endphp
                                                    <div class="flex flex-col items-end">
                                                        <div class="flex items-center gap-1">
                                                            <flux:icon.arrow-down variant="mini" class="size-5 text-red-600" />
                                                            <flux:select
                                                                wire:model.live="structure.{{ $tierIndex }}.divisions.{{ $divisionIndex }}.relegate_count"
                                                                @class([
                                                                    '!border-amber-300 !bg-amber-50' => $showRelegateWarning
                                                                ])
                                                            >
                                                                @foreach (range(0, $division['contestant_count'] - $division['promote_count']) as $i)
                                                                    <flux:select.option>{{ $i }}</flux:select.option>
                                                                @endforeach
                                                            </flux:select>
                                                            @if ($showRelegateWarning)
                                                                <flux:icon.exclamation-circle variant="mini" class="text-amber-500" />
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="flex items-center gap-1">
                                                        <flux:icon.arrow-down variant="mini" class="size-5 text-red-600" />
                                                        <flux:select
                                                            x-bind:disabled="true"
                                                            class="cursor-not-allowed !bg-stone-50"
                                                        >
                                                            <flux:select.option>0</flux:select.option>
                                                        </flux:select>
                                                    </div>
                                                @endif
                                            </div>
                                            <div
                                                x-sort:group="contestants"
                                                x-sort="$wire.sortSlot($item, $position, {{ $tierIndex }}, {{ $divisionIndex }})"
                                                class="w-full space-y-1"
                                            >
                                                @foreach (range(0, $division['contestants'] - 1) as $contestantIndex)
                                                    {{ $contestantIndex }}
                                                    @php
                                                        $divisionPosition = $contestantIndex + 1;
                                                        $slot = $structure[$tierIndex]['divisions'][$divisionIndex]['contestants'][$contestantIndex];
                                                    @endphp
                                                    <div
                                                        x-sort:item="{{ $structure[$tierIndex]['divisions'][$divisionIndex]['contestants'][$contestantIndex]['rank'] }}"
                                                        @class([
                                                            '!bg-white shadow-xs' => $slot['contestant'],
                                                            '!bg-red-50 !border-red-200' => !$slot['contestant'],
                                                            '!border rounded-md flex items-center justify-between gap-2 p-1.5 min-h-10'
                                                        ])
                                                        wire:key="{{ $contestantIndex }}"
                                                    >
                                                        <div class="flex-1 flex items-center gap-1">
                                                            <flux:button size="xs" icon="grip" variant="subtle" class="!shrink-0" x-sort:handle />
                                                            @if ($slot['contestant'])
                                                                <x-generic.member :$club :member="$slot['contestant']->member" />
                                                                <span class="text-xs">#{{ $slot['seed'] }}</span>
                                                                <flux:tooltip>
                                                                    <flux:button
                                                                        wire:click="deleteContestant({{ $slot['contestant']->id }})"
                                                                        icon="eraser"
                                                                        icon:variant="outline"
                                                                        variant="subtle"
                                                                        size="xs"
                                                                    />
                                                                    <flux:tooltip.content class="tooltip">Remove Entrant from Slot</flux:tooltip.content>
                                                                </flux:tooltip>
                                                            @else
                                                                <flux:modal.trigger name="add-contestant-{{ $division['id'] }}-{{ $contestantIndex }}">
                                                                    <flux:tooltip>
                                                                        <flux:button
                                                                            icon="plus"
                                                                            icon:variant="micro"
                                                                            variant="subtle"
                                                                            size="xs"
                                                                        />
                                                                        <flux:tooltip.content class="tooltip">Add Entrant</flux:tooltip.content>
                                                                    </flux:tooltip>
                                                                </flux:modal.trigger>

                                                                @teleport('body')
                                                                    <flux:modal
                                                                        x-data="{
                                                                            selectedEntrantId: $wire.entangle('selectedEntrantToAdd')
                                                                        }"
                                                                        name="add-contestant-{{ $division['id'] }}-{{ $contestantIndex }}"
                                                                        class="modal"
                                                                        x-on:close="selectedEntrantId = null"
                                                                    >
                                                                        <form wire:submit="addContestant({{ $division['id'] }}, {{ $contestantIndex }})">
                                                                            <x-modals.content>
                                                                                <x-slot:heading>{{ __('Add Entrant') }}</x-slot:heading>

                                                                                @if ($this->unallocatedEntrants)
                                                                                    <div class="space-y-3">
                                                                                        <flux:label badge="{{ __('Slot') }} {{ $structure[$tierIndex]['divisions'][$divisionIndex]['contestants'][$contestantIndex]['rank'] }}">{{ __('Position') }} {{ $contestantIndex + 1 }} in {{ $structure[$tierIndex]['divisions'][$divisionIndex]['name'] }}</flux:label>
                                                                                        <flux:select
                                                                                            variant="listbox"
                                                                                            wire:model="selectedEntrantToAdd"
                                                                                            searchable
                                                                                            placeholder="Select entrant..."
                                                                                        >
                                                                                            @foreach ($this->unallocatedEntrants as $member)
                                                                                                <flux:select.option
                                                                                                    value="{{ $member['id'] }}"
                                                                                                    class="w-full"
                                                                                                >
                                                                                                    <div class="flex items-center gap-1">
                                                                                                        <x-generic.member :$club :$member :isLink="false" class="inline-block" />
                                                                                                        <span class="text-xs inline-block">#{{ $member['index'] + 1 }}</span>
                                                                                                    </div>
                                                                                                </flux:select.option>
                                                                                            @endforeach
                                                                                        </flux:select>
                                                                                    </div>

                                                                                    <x-slot:buttons>
                                                                                        <flux:button
                                                                                            type="submit"
                                                                                            variant="primary"
                                                                                            x-bind:disabled="!selectedEntrantId"
                                                                                            :loading="false"
                                                                                        >
                                                                                            {{ __('Add') }}
                                                                                        </flux:button>
                                                                                    </x-slot:buttons>
                                                                                @elseif ($this->entrantCount === 0)
                                                                                    <flux:callout icon="exclamation-circle">
                                                                                        <flux:callout.text>There are no entrants to add.</flux:callout.text>
                                                                                    </flux:callout>
                                                                                @else
                                                                                    <flux:callout icon="exclamation-circle">
                                                                                        <flux:callout.text>All entrants have been allocated.</flux:callout.text>
                                                                                    </flux:callout>
                                                                                @endif
                                                                            </x-modals.content>
                                                                        </form>
                                                                    </flux:modal>
                                                                @endteleport
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <div class="flex items-center gap-2.5 text-xs text-gray-500">
                                                                @if ($slot['contestant'])
                                                                    @if ($slot['seed'] !== $slot['rank'])
                                                                        <flux:tooltip>
                                                                            <flux:icon.exclamation-circle
                                                                                variant="micro"
                                                                                class="text-amber-500"
                                                                            />
                                                                            <flux:tooltip.content class="tooltip">Seed {{ $slot['seed'] }} in Slot {{ $slot['rank'] }}</flux:tooltip.content>
                                                                        </flux:tooltip>
                                                                    @else
                                                                        <flux:tooltip>
                                                                            <flux:icon.check
                                                                                variant="micro"
                                                                                class="text-gray-400"
                                                                            />
                                                                            <flux:tooltip.content class="tooltip">
                                                                                @if ($slot['seed'])
                                                                                    Seed {{ $slot['seed'] }} in
                                                                                @endif
                                                                                Slot {{ $slot['rank'] }}
                                                                            </flux:tooltip.content>
                                                                        </flux:tooltip>
                                                                    @endif
                                                                @else
                                                                    <flux:tooltip>
                                                                        <flux:icon.exclamation-triangle
                                                                            variant="micro"
                                                                            class="mt-0.5 text-red-400"
                                                                        />
                                                                        <flux:tooltip.content class="tooltip">Slot {{ $slot['rank'] }}</flux:tooltip.content>
                                                                    </flux:tooltip>
                                                                @endif
                                                            </div>
                                                            <flux:tooltip>
                                                                <flux:button
                                                                    wire:click="deleteSlot({{ $division['id'] }}, {{ $contestantIndex }}, '{{ $division['name'] }}', {{ $slot['rank'] }})"
                                                                    variant="subtle"
                                                                    icon="trash"
                                                                    icon:variant="outline"
                                                                    size="xs"
                                                                />
                                                                <flux:tooltip.content class="tooltip">Delete Slot</flux:tooltip.content>
                                                            </flux:tooltip>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div class="flex flex-col items-center">
                                        <flux:button
                                            wire:click="addSlot({{ $division['id'] }})"
                                            icon="plus"
                                            variant="primary"
                                            size="sm"
                                        >
                                            {{ __('Slot') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                    <div class="flex flex-col items-center">
                        <flux:button
                            wire:click="addDivision({{ $tier['id'] }})"
                            icon="plus"
                            variant="primary"
                        >
                            {{ __('Division') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endforeach
        <flux:card
            @class([
                '!p-6 !border-l-0 !border-r-0 !border-b-0 !border-t !rounded-none'
            ])
        >
            <div class="flex flex-col items-center">
                <flux:button
                    wire:click="addTier"
                    icon="plus"
                    variant="primary"
                >
                    {{ __('Tier') }}
                </flux:button>
            </div>
        </flux:card>
    </div>
    <div wire:loading class="absolute inset-0 z-20 bg-white -my-3.5 opacity-50"></div>
</div>

@script
<script>
    $js('openModal', (tierIndex, originalTierName) => {
        document.querySelectorAll('[x-ref="error"]').forEach(el => {
            el.classList.add('hidden');
        });
        document.querySelectorAll('[x-ref="error-input"]').forEach(el => {
            el.classList.remove('border-red-500');
        });

        $wire.structure[tierIndex]['name'] = originalTierName
    })
</script>
@endscript