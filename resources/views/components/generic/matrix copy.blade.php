<?php

use Flux\Flux;
use Carbon\Carbon;
use App\Models\Club;
use App\Models\League;
use App\Models\Result;
use App\Models\Session;
use Livewire\Component;
use App\Models\Division;
use Livewire\Attributes\On;
use App\Rules\ValidateScore;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\AddedToLeagueSessionEmail;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public Division $division;

    public Collection $availableMembers;

    public Collection $divisionContestants;

    public ?array $selectedMember = [
        'id' => null
    ];

    public array $matrix = [];

    public ?int $editingHomeId = null;
    public ?int $editingAwayId = null;

    #[Renderless]
    public function openEdit(int $homeId, int $awayId): void
    {
        $this->editingHomeId = $homeId;
        $this->editingAwayId = $awayId;

        // Reset to original values when opening
        $this->matrix[$homeId][$awayId]['for'] = $this->matrix[$homeId][$awayId]['original_for'];
        $this->matrix[$homeId][$awayId]['attended'] = $this->matrix[$homeId][$awayId]['original_attended'];
        $this->matrix[$homeId][$awayId]['date'] = $this->matrix[$homeId][$awayId]['original_date'];
        $this->matrix[$homeId][$awayId]['time'] = $this->matrix[$homeId][$awayId]['original_time'];

        $this->matrix[$awayId][$homeId]['for'] = $this->matrix[$awayId][$homeId]['original_for'];
        $this->matrix[$awayId][$homeId]['attended'] = $this->matrix[$awayId][$homeId]['original_attended'];
        $this->matrix[$awayId][$homeId]['date'] = $this->matrix[$awayId][$homeId]['original_date'];
        $this->matrix[$awayId][$homeId]['time'] = $this->matrix[$awayId][$homeId]['original_time'];

        $this->resetErrorBag();

        $this->dispatch('open-edit-result');
    }

    public function mount()
    {
        $this->resetDivision();
        $this->availableMembers = $this->getAvailableMembers();
        $this->matrix = $this->buildMatrix();
    }

    protected function resetDivision()
    {
        $this->division = $this->division->load([
            'contestants' => fn ($q) => $q->orderBy('index')
                ->with(['member' => fn ($q) => $q->withTrashed()])
                ->withTrashed(),
            'results' => fn ($q) => $q->with(['homeContestant', 'awayContestant'])
        ]);

        $this->divisionContestants = $this->division->contestants()
            ->withTrashed()
            ->orderBy('index')
            ->with(['member' => fn ($q) => $q->withTrashed()])
            ->get();
    }

    protected function buildMatrix(): array
    {
        $matrix = [];
        $contestants = $this->division->contestants()->withTrashed()->get();
        $ids = $contestants->pluck('id')->all();

        $empty = [
            'original_for'   => null,
            'for'            => null,
            'points'         => 0,
            'original_attended' => false,
            'attended'       => false,
            'original_date'  => null,
            'date'           => null,
            'formatted_date' => null,
            'original_time'  => null,
            'time'           => null,
            'formatted_time' => null,
        ];

        // Pre-initialize matrix more efficiently
        foreach ($ids as $r) {
            $matrix[$r] = [];
            foreach ($ids as $c) {
                if ($r === $c) continue; // Skip self-match
                $matrix[$r][$c] = $empty;
            }
        }

        $s = $this->session;

        // Batch process results
        foreach ($this->division->results as $res) {
            $h = $res->home_contestant_id;
            $a = $res->away_contestant_id;

            if (!isset($matrix[$h][$a])) {
                continue; // This continue is fine here - it's in a foreach loop
            }

            $dt = $res->match_at->timezone($this->session->timezone);

            $cellData = [
                'original_date'  => $dt->format('Y-m-d'),
                'date'           => $dt->format('Y-m-d'),
                'formatted_date' => $dt->format('D, j M Y'),
                'original_time'  => $dt->format('H:i'),
                'time'           => $dt->format('H:i'),
                'formatted_time' => $dt->format('g:iA'),
            ];

            // Home contestant data
            $matrix[$h][$a]['original_for'] = $res->home_score;
            $matrix[$h][$a]['for'] = $res->home_score;
            $matrix[$h][$a]['points'] = $this->calculatePoints($res->home_score, $res->away_score, $res->home_attended, $s);
            $matrix[$h][$a]['original_attended'] = !$res->home_attended;
            $matrix[$h][$a]['attended'] = !$res->home_attended;
            $matrix[$h][$a] = array_merge($matrix[$h][$a], $cellData);

            // Away contestant data
            $matrix[$a][$h]['original_for'] = $res->away_score;
            $matrix[$a][$h]['for'] = $res->away_score;
            $matrix[$a][$h]['points'] = $this->calculatePoints($res->away_score, $res->home_score, $res->away_attended, $s);
            $matrix[$a][$h]['original_attended'] = !$res->away_attended;
            $matrix[$a][$h]['attended'] = !$res->away_attended;
            $matrix[$a][$h] = array_merge($matrix[$a][$h], $cellData);
        }

        return $matrix;
    }

    protected function calculatePoints($score, $opponentScore, $attended, $session)
    {
        $points = 0;
        if ($score > $opponentScore) {
            $points += $session->pts_win;
        } elseif ($score === $opponentScore) {
            $points += $session->pts_draw;
        }

        if ($attended) {
            $points += $session->pts_play;
        }

        return $points + ($score * $session->pts_per_set);
    }

    public function delete($home_contestant_id, $away_contestant_id)
    {
        $this->division->results()
            ->whereIn('home_contestant_id', [$home_contestant_id, $away_contestant_id])
            ->whereIn('away_contestant_id', [$home_contestant_id, $away_contestant_id])
            ->delete();

        $this->resetDivision();
        $this->matrix = $this->buildMatrix();
        Flux::modals()->close();

        Flux::toast(
            variant: 'success',
            text: 'Result deleted.'
        );
    }

    public function save($home_contestant_id, $away_contestant_id)
    {
        // keep originals
        $origHomeId = (int) $home_contestant_id;
        $origAwayId = (int) $away_contestant_id;

        $homeScore = $this->matrix[$origHomeId][$origAwayId]['for'];
        $awayScore = $this->matrix[$origAwayId][$origHomeId]['for'];

        $home_attended = (bool) ($this->matrix[$origHomeId][$origAwayId]['attended'] ?? false) ? 0 : 1;
        $away_attended = (bool) ($this->matrix[$origAwayId][$origHomeId]['attended'] ?? false) ? 0 : 1;

        // 1) basic field validation first
        $this->validate([
            "matrix.$origHomeId.$origAwayId.date" => ['required', 'date'],
            "matrix.$origHomeId.$origAwayId.time" => ['required'],
            "matrix.$origAwayId.$origHomeId.for"  => ['required', new ValidateScore($homeScore, $awayScore, $this->league->best_of)],
            "matrix.$origHomeId.$origAwayId.for"  => ['required', new ValidateScore($awayScore, $homeScore, $this->league->best_of)],
        ], [
            "matrix.$origHomeId.$origAwayId.date.required" => "Match Date is required.",
            "matrix.$origHomeId.$origAwayId.time.required" => "Match Time is required."
        ]);

        // 2) build the UTC instant from session tz (you already do this)
        $utcDateTime = Carbon::parse(
            $this->matrix[$origHomeId][$origAwayId]['date'].' '.
            $this->matrix[$origHomeId][$origAwayId]['time'],
            $this->session->timezone
        )->utc();

        $nowUtc = Carbon::now('UTC');
        if ($utcDateTime->gt($nowUtc)) {
            $msg  = __("Match Date/Time can't be in the future.");
            $base = "matrix.$origHomeId.$origAwayId";
            $this->addError("$base.date", $msg);
            $this->addError("$base.time", $msg);
            return;
        }

        // 3) enforce: starts_at <= match_at < ends_at  (exclusive end recommended)
        $startUtc = $this->session->starts_at;
        $endUtc   = $this->session->ends_at;

        $clubTz = $this->session->timezone ?? $this->league->club->timezone ?? 'UTC';
        $startLocal = $startUtc?->clone()->timezone($clubTz)->format('j M Y H:i');
        $endLocal   = $endUtc?->clone()->timezone($clubTz)->format('j M Y H:i');

        if ($utcDateTime->lt($startUtc) || ($endUtc && $utcDateTime->gte($endUtc))) {
            $msg = __("Active period (:start → :end)", ['start' => $startLocal, 'end' => $endLocal]);

            // attach to both fields so both inputs highlight
            $base = "matrix.$origHomeId.$origAwayId";
            $this->addError("$base.date", $msg);
            $this->addError("$base.time", $msg);
            return;
        }

        // normalize ids (lower -> home)
        $homeId = min($origHomeId, $origAwayId);
        $awayId = max($origHomeId, $origAwayId);

        // was the original pair already in normalized order?
        $isOriginalOrder = ($origHomeId === $homeId && $origAwayId === $awayId);

        Result::updateOrCreate(
            [
                'club_id' => $this->club->id,
                'league_id' => $this->league->id,
                'league_session_id' => $this->session->id,
                'division_id'        => $this->division->id,
                'home_contestant_id' => $homeId,
                'away_contestant_id' => $awayId,
            ],
            [
                'match_at'   => $utcDateTime,
                'home_score' => $isOriginalOrder ? $homeScore : $awayScore,
                'away_score' => $isOriginalOrder ? $awayScore : $homeScore,
                'home_attended' => $isOriginalOrder ? $home_attended : $away_attended, // invert so checked checkbox means not attended
                'away_attended' => $isOriginalOrder ? $away_attended : $home_attended, // invert so checked checkbox means not attended
                'submitted_by' => Auth::id(),
                'submitted_by_admin' => true,
            ]
        );

        $this->resetDivision();

        $this->matrix = $this->buildMatrix();

        Flux::modals()->close();

        Flux::toast(
            variant: 'success',
            text: 'Result saved.'
        );

        $this->editingHomeId = null;
        $this->editingAwayId = null;
    }

    protected function getAvailableMembers() {
        $excludedIds = $this->session->membersInSession();
        $availableMembers = $this->club->members()->whereNotIn('id', $excludedIds)->sortByName()->get();

        return $availableMembers;
    }

    public function addMember()
    {
        $divisionContestants = $this->division->contestants();

        DB::transaction(function () use ($divisionContestants) {
            $contestant = $divisionContestants->create([
                'league_session_id' => $this->session->id,
                'division_id' => $this->division->id,
                'member_id' => $this->selectedMember['id'],
                'index' => ($divisionContestants->max('index') + 1)
            ]);

            $this->division->increment('contestant_count');

            $user = $contestant->member?->user;

            if ($user && $this->session->isPublished()) {
                $contestant->notify();

                Mail::to($user->email)->queue(new AddedToLeagueSessionEmail($this->club, $this->league, $this->session, $user));
            }
        });

        $this->resetDivision();

        $this->selectedMember['id'] = null;

        $this->availableMembers = $this->getAvailableMembers();

        $this->matrix = $this->buildMatrix();

        Flux::modals()->close('add-member');

        $this->dispatch('competitor-added');

        Flux::toast(
            variant: 'success',
            text: 'Competitor added.'
        );
    }

    public function withdraw(int $contestantId)
    {
        $contestant = $this->division->contestants()->findOrFail($contestantId);

        $contestant->delete();

        $this->resetDivision();

        Flux::modals()->close();

        Flux::toast(
            variant: 'success',
            text: 'Competitor withdrawn.'
        );
    }

    public function reinstate(int $contestantId)
    {
        $contestant = $this->division->contestants()->findOrFail($contestantId);

        $contestant->restore();

        $this->resetDivision();

        Flux::modals()->close();

        Flux::toast(
            variant: 'success',
            text: 'Competitor reinstated.'
        );
    }

    public function removeContestant(int $contestantId): void
    {
        DB::transaction(function () use ($contestantId) {
            // Lock the division row and fetch the target contestant (even if soft-deleted handling is not needed here)
            // $division   = $this->division->lockForUpdate()->first(); // ensure fresh + locked
            $contestant = $this->division->contestants()->withTrashed()->findOrFail($contestantId);

            $removedIndex = (int) $contestant->index;

            // Hard delete (use delete() for soft-delete)
            $contestant->deleteFromDivision();

            // Recompute counts & apply your rule
            $newCount = max(0, (int) $this->division->contestant_count - 1);
            $promote  = (int) $this->division->promote_count;
            $relegate = (int) $this->division->relegate_count;

            // If contestant_count AFTER removal is greater than (promote + relegate),
            // decrement whichever of promote/relegate is larger (if > 0).
            if ($newCount < ($promote + $relegate)) {
                if ($promote >= $relegate && $promote > 0) {
                    $promote -= 1;
                } elseif ($relegate > 0) {
                    $relegate -= 1;
                }
            }

            // Persist division updates in one save
            $this->division->update([
                'contestant_count' => $newCount,
                'promote_count'    => $promote,
                'relegate_count'   => $relegate,
            ]);

            // Close the index gap for remaining contestants
            $this->division->contestants()
                ->where('index', '>', $removedIndex)
                ->orderBy('index')
                ->decrement('index');

            // Optional hard reindex to guarantee contiguous 0..n-1
            $remaining = $this->division->contestants()->withTrashed()->orderBy('index')->get(['id','index']);
            foreach ($remaining as $i => $c) {
                if ((int) $c->index !== $i) {
                    $c->update(['index' => $i]);
                }
            }
        });

        // Refresh cached relations / UI state
        $this->resetDivision();

        $this->availableMembers = $this->getAvailableMembers();

        $this->matrix = $this->buildMatrix();

        $this->dispatch('competitor-removed');

        Flux::toast(
            variant: 'success',
            text: 'Competitor removed.'
        );
    }

    #[On('date-updated')]
    public function refreshMatrix()
    { }

    #[Computed]
    public function maxTally()
    {
        return floor($this->league->best_of / 2) + $this->league->best_of % 2;
    }
};
?>

<div class="relative space-y-8">
    @php
        $tier = $division->tier;
        $tierCount = $session->tiers()->count();
        $showPromote = $tier->index > 0;
        $showRelegate = $tier->index + 1 < $tierCount;
    @endphp
    @if ($showPromote || $showRelegate || is_null($session->processed_at))
        <div class="flex items-center justify-between min-h-10">
            @if ($showPromote || $tier->index + 1 < $tierCount && $division->relegate_count > 0)
                <div class="sm:flex sm:items-center gap-6">
                    @if ($showPromote)
                        <div class="flex items-center gap-0.5">
                            <flux:text>Promote</flux:text>
                            <flux:icon.arrow-up variant="micro" class="ml-0.5 size-4 text-green-600" />
                            <flux:heading class="!text-green-600">{{ $division->promote_count }}</flux:heading>
                        </div>
                    @endif
                    @if ($showRelegate)
                        <div class="flex items-center gap-0.5">
                            <flux:text>Relegate</flux:text>
                            <flux:icon.arrow-down variant="micro" class="ml-0.5 size-4 text-red-600" />
                            <flux:heading class="!text-red-600">{{ $division->relegate_count }}</flux:heading>
                        </div>
                    @endif
                </div>
            @endif
            @if (is_null($session->processed_at))
                <div>
                    <flux:modal.trigger name="add-member">
                        <flux:button
                            variant="primary"
                            icon="plus"
                        >
                            Competitor
                        </flux:button>
                    </flux:modal.trigger>

                    @teleport('body')
                        <flux:modal name="add-member" class="modal">
                            <form wire:submit="addMember">
                                <x-modals.content>
                                    <x-slot:heading>{{ __('Add Competitor') }}</x-slot:heading>
                                    @if (count($availableMembers) === 0)
                                        <flux:callout variant="secondary" icon="information-circle">
                                            {{ __('All members are competing in this league session.') }}
                                        </flux:callout>
                                    @else
                                        <flux:text>Select the member you wish to add to this division.</flux:text>
                                        <flux:field>
                                            <flux:select variant="listbox" wire:model="selectedMember.id" searchable clearable placeholder="Select member...">
                                                @foreach ($availableMembers as $member)
                                                    <flux:select.option value="{{ $member->id }}">
                                                        <x-generic.member :$club :$member :isLink="false" />
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </flux:field>
                                        <x-slot:buttons>
                                            <flux:button
                                                type="submit"
                                                variant="primary"
                                                x-bind:disabled="!$wire.selectedMember.id"
                                                :loading="false"
                                            >
                                                Add
                                            </flux:button>
                                        </x-slot:buttons>
                                    @endif
                                </x-modals.content>
                            </form>
                        </flux:modal>
                    @endteleport
                </div>
            @endif
        </div>
    @endif

    @php
        $tz = $session->timezone ?? $league->club->timezone ?? 'UTC';

        // MIN = session start (local date)
        $minDate = $session->starts_at->timezone($tz)->toDateString();   // "YYYY-MM-DD"

        // MAX candidates
        $todayLocal = now()->timezone($tz);
        $endLocalForMax = optional($session->ends_at)->timezone($tz);

        $maxLocal = collect([$endLocalForMax, $todayLocal])->filter()->min(); // earliest of the two
        $maxDate  = $maxLocal?->toDateString();
    @endphp

    <flux:table
        class="border"
    >
        <flux:table.columns class="bg-stone-50">
            <flux:table.column
                class="sm:sticky sm:left-0 sm:z-10 sm:shadow bg-stone-50"
            ></flux:table.column>
            @foreach ($divisionContestants as $col)
                <flux:table.column :key="$col['id']" class="border-l" align="center">
                    <div class="flex flex-col items-center">
                        <div class="flex flex-col items-center @if($col['deleted_at']) opacity-50 @endif">
                            <div>{{ $col->member->first_name }}</div>
                            <div>{{ $col->member->last_name }}</div>
                        </div>
                        @if ($col->deleted_at)
                            <flux:badge color="red" size="sm" class="mt-1">WD</flux:badge>
                        @endif
                    </div>
                </flux:table.column>
            @endforeach
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($divisionContestants as $row)
            <div x-text="scrolled"></div>
                <flux:table.row :key="$row['id']">
                    <flux:table.cell
                        class="sm:sticky sm:left-0 sm:z-10 sm:shadow bg-white h-28"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex-1 flex items-center justify-between gap-2">
                                <div class="@if($row['deleted_at']) opacity-50 @endif">
                                    <flux:heading>
                                        <x-generic.member :$club :member="$row['member']" :showTel="true" />
                                    </flux:heading>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                @if ($row['deleted_at'])
                                    <flux:badge color="red" size="sm" class="ml-2">WD</flux:badge>
                                    @if (is_null($session->processed_at))
                                        <flux:modal.trigger name="matrix-reinstate-{{ $row['id'] }}">
                                            <flux:tooltip>
                                                <flux:button
                                                    variant="subtle"
                                                    size="xs"
                                                    icon="circle-plus"
                                                    class="ml-2"
                                                />
                                                <flux:tooltip.content>
                                                    Reinstate
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        </flux:modal.trigger>
                                    @endif

                                    @teleport('body')
                                        <flux:modal name="matrix-reinstate-{{ $row['id'] }}" class="modal">
                                            <form wire:submit="reinstate({{ $row['id'] }})">
                                                <x-modals.content>
                                                    <x-slot:heading>{{ __('Reinstate Competitor') }}</x-slot:heading>
                                                    <flux:text>Are you sure you wish to reinstate {{ $row->member->full_name }}?</flux:text>
                                                    <x-slot:buttons>
                                                        <flux:button type="submit" variant="primary">{{ __('Reinstate') }}</flux:button>
                                                    </x-slot:buttons>
                                                </x-modals.content>
                                            </form>
                                        </flux:modal>
                                    @endteleport

                                @else
                                    @if (is_null($session->processed_at))
                                        <flux:modal.trigger name="matrix-withdraw-{{ $row['id'] }}">
                                            <flux:tooltip>
                                                <flux:button
                                                    variant="subtle"
                                                    size="xs"
                                                    icon="circle-minus"
                                                    class="ml-2"
                                                />
                                                <flux:tooltip.content class="tooltip">
                                                    Withdraw
                                                </flux:tooltip.content>
                                            </flux:tooltip>
                                        </flux:modal.trigger>
                                    @endif

                                    @teleport('body')
                                        <flux:modal name="matrix-withdraw-{{ $row['id'] }}" class="modal">
                                            <form wire:submit="withdraw({{ $row['id'] }})">
                                                <x-modals.content>
                                                    <x-slot:heading>{{ __('Withdraw Competitor') }}</x-slot:heading>
                                                    <flux:text>All results involving this competitor will be ignored.</flux:text>
                                                    <flux:text>Are you sure you wish to withdraw {{ $row->member->full_name }}?</flux:text>
                                                    <flux:text>You will be able to reinstate the competitor at any time without any results being lost.</flux:text>
                                                    <x-slot:buttons>
                                                        <flux:button type="submit" variant="danger">{{ __('Withdraw') }}</flux:button>
                                                    </x-slot:buttons>
                                                </x-modals.content>
                                            </form>
                                        </flux:modal>
                                    @endteleport
                                @endif

                                @if (is_null($session->processed_at) && $division->contestant_count > 1)
                                    <flux:modal.trigger name="matrix-delete-{{ $row->id }}">
                                        <flux:tooltip>
                                            <flux:button
                                                variant="subtle"
                                                size="xs"
                                                icon="trash"
                                                icon:variant="outline"
                                            />
                                            <flux:tooltip.content>
                                                Remove
                                            </flux:tooltip.content>
                                        </flux:tooltip>
                                    </flux:modal.trigger>

                                    @teleport('body')
                                        <flux:modal name="matrix-delete-{{ $row->id }}" class="modal">
                                            <form wire:submit="removeContestant({{ $row->id }})">
                                                <x-modals.content>
                                                    <x-slot:heading>{{ __('Remove Competitor') }}</x-slot:heading>
                                                    <flux:text>Are you sure you wish to remove {{ $row->member->full_name }} from this division?</flux:text>
                                                    <flux:text>All results involving them will be permanently deleted!</flux:text>
                                                    <x-slot:buttons>
                                                        <flux:button type="submit" variant="danger">{{ __('Remove') }}</flux:button>
                                                    </x-slot:buttons>
                                                </x-modals.content>
                                            </form>
                                        </flux:modal>
                                    @endteleport
                                @endif

                            </div>
                        </div>
                    </flux:table.cell>
                    @foreach ($divisionContestants as $col)
                        <flux:table.cell
                            wire:key="cell-{{ $row->id }}-{{ $col->id }}"
                            @class([
                                'relative size-28 !min-w-28',
                                'bg-gray-500' => $row->id === $col->id,
                                'border-l' => $row->id !== $col->id,
                            ])
                        >
                            @if ($row->id !== $col->id)
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div
                                        @class([
                                            'absolute inset-0 flex flex-col items-center justify-center',
                                            'opacity-50 bg-stone-100' => $col['deleted_at'] || $row['deleted_at']
                                        ])
                                    >
                                        @if ($matrix[$row->id][$col->id]['original_for'] || $matrix[$col->id][$row->id]['original_for'])
                                            <flux:text class="!text-xs">{{ $matrix[$row->id][$col->id]['formatted_date'] }}</flux:text>
                                            <div
                                                @class([
                                                    'flex items-center font-medium text-2xl text-zinc-900',
                                                ])
                                                size="lg"
                                            >
                                                @if ($matrix[$row->id][$col->id]['original_attended'])
                                                    <flux:icon.exclamation-circle variant="micro" class="size-4 text-amber-500 mr-0.5" />
                                                @elseif ($matrix[$col->id][$row->id]['original_attended'])
                                                    <div class="size-4"></div>
                                                @endif
                                                {{ $matrix[$row->id][$col->id]['original_for'] }}-{{ $matrix[$col->id][$row->id]['original_for'] }}
                                                @if ($matrix[$col->id][$row->id]['original_attended'])
                                                    <flux:icon.exclamation-circle variant="micro" class="size-4 text-amber-500 ml-0.5" />
                                                @elseif ($matrix[$row->id][$col->id]['original_attended'])
                                                    <div class="size-4"></div>
                                                @endif
                                            </div>
                                            <flux:text class="!text-xs">{{ $matrix[$row->id][$col->id]['formatted_time'] }}</flux:text>
                                        @endif
                                    </div>
                                </div>

                                <div
                                    x-data="{
                                        hasErrors: '',
                                        originalScores: {
                                            score1: $wire.matrix[{{ $row->id }}][{{ $col->id }}]['for'],
                                            score2: $wire.matrix[{{ $col->id }}][{{ $row->id }}]['for']
                                        }
                                    }"
                                    x-init="$nextTick(() => { hasErrors = {{ json_encode($errors->isNotEmpty()) }} })"
                                    class="absolute inset-0"
                                >
                                    @if (is_null($session->processed_at))
                                        @if (! ($matrix[$row->id][$col->id]['original_for'] || $matrix[$col->id][$row->id]['original_for']))
                                            <div class="flex items-center justify-center size-full">
                                                <flux:icon.plus-circle
                                                    variant="solid"
                                                    class="size-8 text-blue-300"
                                                />
                                            </div>
                                        @else
                                            <div class="absolute top-0.5 right-0.5">
                                                <flux:icon.pencil-square
                                                    variant="outline"
                                                    class="size-5 text-blue-300"
                                                />
                                            </div>
                                        @endif
                                        <button
                                            x-on:click="$wire.openEdit({{ $row->id }}, {{ $col->id }});"
                                            class="absolute inset-0 hover:bg-blue-400 opacity-10"
                                        />
                                    @endif
                                </div>
                            @endif
                        </flux:table.cell>
                    @endforeach
                </flux:table.row>
            @endforeach

            @teleport('body')
                <flux:modal
                    name="edit-result"
                    class="modal"
                    x-on:close="hasErrors = false"
                    x-data="{
                        hasErrors: false,
                        init() {
                            // Listen for the open event from Livewire
                            Livewire.on('open-edit-result', () => {
                                this.hasErrors = false;
                                $flux.modal('edit-result').show();
                            });
                        }
                    }"
                >
                    @if($editingHomeId && $editingAwayId)
                        @php
                            $homeId = $editingHomeId;
                            $awayId = $editingAwayId;
                            $homeContestant = $divisionContestants->firstWhere('id', $homeId);
                            $awayContestant = $divisionContestants->firstWhere('id', $awayId);
                        @endphp

                        <form wire:submit="save({{ $homeId }}, {{ $awayId }})" class="space-y-6">
                            <x-modals.content>
                                <x-slot:heading>{{ __('Submit Result') }}</x-slot:heading>

                                <div>
                                    <div class="flex items-center justify-center gap-2">
                                        <flux:field>
                                            <flux:label>{{ __('Match Date') }}</flux:label>
                                            <flux:date-picker
                                                wire:model="matrix.{{ $homeId }}.{{ $awayId }}.date"
                                                :min="$minDate"
                                                :max="$maxDate"
                                            />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>{{ __('Time') }}</flux:label>
                                            <flux:time-picker
                                                type="input"
                                                wire:model="matrix.{{ $homeId }}.{{ $awayId }}.time"
                                                time-format="12-hour"
                                                :dropdown="false"
                                            />
                                        </flux:field>
                                    </div>

                                    @php
                                        $dateKey = "matrix.$homeId.$awayId.date";
                                        $timeKey = "matrix.$homeId.$awayId.time";
                                        $dateErr = $errors->first($dateKey);
                                        $timeErr = $errors->first($timeKey);
                                        $msg = $dateErr ?: $timeErr;
                                    @endphp

                                    @if ($msg)
                                        <div class="text-center">
                                            <flux:error :message="$msg" />
                                        </div>
                                    @endif
                                </div>

                                <div class="space-y-2 mt-6">
                                    <flux:text class="text-center">Best of {{ $league->best_of }} {{ Str::lower($league->tallyUnit->name) }}</flux:text>

                                    @php
                                        $homeKey = "matrix.$homeId.$awayId";
                                        $awayKey = "matrix.$awayId.$homeId";
                                    @endphp

                                    <div
                                        wire:key="result-state-{{ $homeId }}-{{ $awayId }}"
                                        x-data="{
                                            homeChecked: @entangle("matrix.$homeId.$awayId.attended"),
                                            awayChecked: @entangle("matrix.$awayId.$homeId.attended"),
                                            homeScore: @entangle("matrix.$homeId.$awayId.for"),
                                            awayScore: @entangle("matrix.$awayId.$homeId.for"),
                                            maxScore: {{ $this->maxTally }},

                                            setScores(side) {
                                                if (side === 'home' && this.homeChecked) {
                                                    this.homeScore = 0;
                                                    this.awayScore = this.maxScore;
                                                } else if (side === 'away' && this.awayChecked) {
                                                    this.awayScore = 0;
                                                    this.homeScore = this.maxScore;
                                                }
                                            },

                                            onHomeToggle() {
                                                this.homeChecked = !this.homeChecked;
                                                if (this.homeChecked) {
                                                    this.awayChecked = false;
                                                    this.setScores('home');
                                                } else {
                                                    if (!this.awayChecked) {
                                                        this.homeScore = '';
                                                        this.awayScore = '';
                                                    } else {
                                                        this.setScores('away');
                                                    }
                                                }
                                            },

                                            onAwayToggle() {
                                                this.awayChecked = !this.awayChecked;
                                                if (this.awayChecked) {
                                                    this.homeChecked = false;
                                                    this.setScores('away');
                                                } else {
                                                    if (!this.homeChecked) {
                                                        this.homeScore = '';
                                                        this.awayScore = '';
                                                    } else {
                                                        this.setScores('home');
                                                    }
                                                }
                                            }
                                        }"
                                        class="flex flex-col items-center"
                                    >
                                        <!-- HOME -->
                                        <table>
                                            <tr>
                                                <td class="p-1 pl-0 pr-4">
                                                    <flux:checkbox
                                                        @click="onHomeToggle()"
                                                        x-bind:checked="homeChecked"
                                                        class="!-mt-px"
                                                    />
                                                </td>
                                                <td class="p-1">
                                                    <flux:heading
                                                        class="text-right"
                                                        x-bind:class="{ 'line-through text-gray-400': homeChecked }"
                                                    >
                                                        {{ $homeContestant->member->full_name }}
                                                    </flux:heading>
                                                </td>
                                                <td class="p-1 pr-0">
                                                    <flux:select
                                                        x-bind:disabled="homeChecked || awayChecked"
                                                        wire:model="{{ $homeKey }}.for"
                                                    >
                                                        <flux:select.option value="">-</flux:select.option>
                                                        @for ($i = 0; $i <= $this->maxTally; $i++)
                                                            <flux:select.option value="{{ $i }}">{{ $i }}</flux:select.option>
                                                        @endfor
                                                    </flux:select>
                                                </td>
                                            </tr>

                                            <!-- AWAY -->
                                            <tr>
                                                <td class="p-1 pl-0 pr-4">
                                                    <flux:checkbox
                                                        @click="onAwayToggle()"
                                                        x-bind:checked="awayChecked"
                                                        class="!-mt-px"
                                                    />
                                                </td>
                                                <td class="p-1">
                                                    <flux:heading
                                                        class="text-right"
                                                        x-bind:class="{ 'line-through text-gray-400': awayChecked }"
                                                    >
                                                        {{ $awayContestant->member->full_name }}
                                                    </flux:heading>
                                                </td>
                                                <td class="p-1 pr-0">
                                                    <flux:select
                                                        x-bind:disabled="homeChecked || awayChecked"
                                                        wire:model="{{ $awayKey }}.for"
                                                    >
                                                        <flux:select.option value="">-</flux:select.option>
                                                        @for ($i = 0; $i <= $this->maxTally; $i++)
                                                            <flux:select.option value="{{ $i }}">{{ $i }}</flux:select.option>
                                                        @endfor
                                                    </flux:select>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    @if ($errors->has('matrix.*.*.for') || $errors->has('matrix'))
                                        <flux:error message="{{ __('Invalid Score') }}" class="text-center" />
                                    @endif

                                    <div class="flex flex-col items-center">
                                        <flux:text class="text-center w-80 !text-xs">Tick a box if a player didn't turn up so their opponent can claim the points.</flux:text>
                                    </div>
                                </div>

                                <x-slot:buttons>
                                    <flux:button
                                        wire:click="delete({{ $homeId }}, {{ $awayId }})"
                                        type="button"
                                        variant="danger"
                                        x-bind:isLoading="true"
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                    <flux:button type="submit" variant="primary">
                                        {{ __('Save') }}
                                    </flux:button>
                                </x-slot:buttons>
                            </x-modals.content>
                        </form>
                    @endif
                </flux:modal>
            @endteleport

        </flux:table.rows>
    </flux:table>

    <div wire:loading class="absolute inset-0 z-20 bg-white -my-3.5 opacity-50"></div>

</div>

<script>
    this.$js.openModal = (home_contestant_id, away_contestant_id) => {
        document.querySelector('[x-ref="error"]').forEach(el => {
            el.classList.add('hidden');
        });
        document.querySelectorAll('[x-ref="error-border"]').forEach(el => {
            el.classList.remove('border-red-500');
            button = el.children[0];
            if (button) {
                button.classList.remove('border-red-500');
            }
        });
        document.querySelectorAll('[x-ref="homeSelect"]').forEach(el => {
            el.classList.remove('border-red-500');
            button = el.children[0];
            if (button) {
                button.classList.remove('border-red-500');
            }
        });
        document.querySelectorAll('[x-ref="awaySelect"]').forEach(el => {
            el.classList.remove('border-red-500');
            button = el.children[0];
            if (button) {
                button.classList.remove('border-red-500');
            }
        });
        $wire.matrix[home_contestant_id][away_contestant_id].for = $wire.matrix[home_contestant_id][away_contestant_id].original_for
        $wire.matrix[home_contestant_id][away_contestant_id].attended = $wire.matrix[home_contestant_id][away_contestant_id].original_attended
        $wire.matrix[home_contestant_id][away_contestant_id].date = $wire.matrix[home_contestant_id][away_contestant_id].original_date
        $wire.matrix[home_contestant_id][away_contestant_id].time = $wire.matrix[home_contestant_id][away_contestant_id].original_time
        $wire.matrix[away_contestant_id][home_contestant_id].for = $wire.matrix[away_contestant_id][home_contestant_id].original_for
        $wire.matrix[away_contestant_id][home_contestant_id].attended = $wire.matrix[away_contestant_id][home_contestant_id].original_attended
        $wire.matrix[away_contestant_id][home_contestant_id].date = $wire.matrix[away_contestant_id][home_contestant_id].original_date
        $wire.matrix[away_contestant_id][home_contestant_id].time = $wire.matrix[away_contestant_id][home_contestant_id].original_time
    }
</script>