<?php

use Flux\Flux;
use Carbon\Carbon;
use App\Models\Club;
use App\Models\League;
use App\Models\Result;
use App\Models\Session;
use App\Models\Division;
use App\Rules\ScoreBestOf;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public Division $division;

    public Collection $availableMembers;

    public int $promoteCount;

    public int $relegateCount;

    public string $tab;

    public ?array $selectedMember = [
        'id' => null
    ];

    public array $matrix = [];

    public function mount(Division $division)
    {
        $this->resetDivision();
        $this->availableMembers = $this->getAvailableMembers();
        $this->matrix = $this->buildMatrix();
    }

    protected function resetDivision()
    {
        $this->division = $this->division->load(['contestants' => fn ($q) => $q->orderBy('index')->with('member')->withTrashed(), 'results']);
    }

    protected function buildMatrix(): array
    {
        $matrix = [];
        $ids = $this->division->contestants->pluck('id')->all();
        $empty = [
            'original_for'   => null,
            'for'            => null,
            'points'         => 0,
            'original_date'  => null,
            'date'           => null,
            'formatted_date' => null,
            'original_time'  => null,
            'time'           => null,
            'formatted_time' => null,
        ];

        // Initialize matrix
        foreach ($ids as $r) {
            $matrix[$r] = array_fill_keys(array_diff($ids, [$r]), $empty);
        }

        $s = $this->session;

        foreach ($this->division->results as $res) {
            $h = $res->home_contestant_id;
            $a = $res->away_contestant_id;

            if (!isset($matrix[$h][$a]) || !isset($matrix[$a][$h])) {
                continue; // Skip if contestants no longer exist
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

            $matrix[$h][$a] = array_merge($empty, $cellData, [
                'original_for' => $res->home_score,
                'for'         => $res->home_score,
                'points'      => ($res->home_score > $res->away_score ? $s->pts_win : ($res->home_score === $res->away_score ? $s->pts_draw : 0))
                            + ($res->home_attended ? $s->pts_play : 0)
                            + ($res->home_score * $s->pts_per_set),
            ]);

            $matrix[$a][$h] = array_merge($empty, $cellData, [
                'original_for' => $res->away_score,
                'for'         => $res->away_score,
                'points'      => ($res->away_score > $res->home_score ? $s->pts_win : ($res->away_score === $res->home_score ? $s->pts_draw : 0))
                            + ($res->away_attended ? $s->pts_play : 0)
                            + ($res->away_score * $s->pts_per_set),
            ]);
        }

        return $matrix;
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

        $home_score = $this->matrix[$origHomeId][$origAwayId]['for'];
        $away_score = $this->matrix[$origAwayId][$origHomeId]['for'];

        // 1) basic field validation first
        $this->validate([
            "matrix.$home_contestant_id.$away_contestant_id.date" => ['required', 'date'],
            "matrix.$home_contestant_id.$away_contestant_id.time" => ['required'],
            "matrix.$away_contestant_id.$home_contestant_id.for"  => ['required'],
            "matrix.$home_contestant_id.$away_contestant_id.for"  => ['required'],
        ], [
            "matrix.$home_contestant_id.$away_contestant_id.date.required" => "Match Date is required.",
            "matrix.$home_contestant_id.$away_contestant_id.time.required" => "Match Time is required."
        ]);

        // 2) build the UTC instant from session tz (you already do this)
        $utcDateTime = Carbon::parse(
            $this->matrix[$home_contestant_id][$away_contestant_id]['date'].' '.
            $this->matrix[$home_contestant_id][$away_contestant_id]['time'],
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
                'home_score' => $isOriginalOrder ? $home_score : $away_score,
                'away_score' => $isOriginalOrder ? $away_score : $home_score,
                'submitted_by' => Auth::id(),
            ]
        );

        $this->resetDivision();

        $this->matrix = $this->buildMatrix();

        Flux::modals()->close();

        Flux::toast(
            variant: 'success',
            text: 'Result saved.'
        );
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
            $divisionContestants->create([
                'member_id' => $this->selectedMember['id'],
                'index' => $divisionContestants->max('index') + 1
            ]);

            $this->division->increment('contestant_count');
        });

        $this->resetDivision();

        $this->selectedMember['id'] = null;

        $this->availableMembers = $this->getAvailableMembers();

        $this->matrix = $this->buildMatrix();

        Flux::modals()->close('add-member');

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
        $contestant = $this->division->contestants()->withTrashed()->findOrFail($contestantId);

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

        Flux::toast(
            variant: 'success',
            text: 'Contestant removed.'
        );
    }

    #[Computed]
    public function divisionContestants(): Collection
    {
        return $this->division->contestants()
            ->withTrashed()
            ->orderBy('index')
            ->with(['member' => fn ($q) => $q->withTrashed()])
            ->get();
    }

    #[Computed]
    public function maxTally()
    {
        return floor($this->league->best_of / 2) + $this->league->best_of % 2;
    }
};
?>

<div class="relative space-y-6">

    <div class="flex flex-col items-end">
        <flux:modal.trigger name="add-member" class="mt-6">
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
                                        <flux:select.option value="{{ $member->id }}">{{ $member->full_name }}<span class="ml-1 text-gray-500 text-xs">M{{ $member->id }}</span></flux:select.option>
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
                                    Save
                                </flux:button>
                            </x-slot:buttons>
                        @endif
                    </x-modals.content>
                </form>
            </flux:modal>
        @endteleport
    </div>

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

    <flux:table class="border">
        <flux:table.columns class="bg-gray-100">
            <flux:table.column></flux:table.column>
            @foreach ($this->divisionContestants as $col)
                <flux:table.column class="border-l" align="center">
                    <div class="flex flex-col items-center">
                        <div class="flex flex-col items-center @if($col['deleted_at']) opacity-50 @endif">
                            <div>{{ $col->member->first_name }}</div>
                            <div>{{ $col->member->last_name }}</div>
                        </div>
                        @if ($col['deleted_at'])
                            <flux:badge color="red" size="sm" class="mt-1">WD</flux:badge>
                        @endif
                    </div>
                </flux:table.column>
            @endforeach
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->divisionContestants as $row)
                <flux:table.row wire:key="{{ $row['id'] }}">
                    <flux:table.cell class="h-28 flex items-center justify-between gap-4">
                        <div class="@if($row['deleted_at']) opacity-50 @endif">
                            <flux:heading>{{ $row->member->full_name }}<span class="ml-1 text-xs text-gray-500 font-base">M{{ $row->member->id }}</span></flux:heading>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($row['deleted_at'])
                                <flux:badge color="red" size="sm">WD</flux:badge>
                                @if (is_null($session->processed_at))
                                    <flux:modal.trigger name="matrix-reinstate-{{ $row['id'] }}">
                                        <flux:button
                                            variant="subtle"
                                            size="xs"
                                            icon="arrow-path"
                                        />
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
                                        <flux:button
                                            variant="subtle"
                                            size="xs"
                                            icon="no-symbol"
                                        />
                                    </flux:modal.trigger>
                                @endif

                                @teleport('body')
                                    <flux:modal name="matrix-withdraw-{{ $row['id'] }}" class="modal">
                                        <form wire:submit="withdraw({{ $row['id'] }})">
                                            <x-modals.content>
                                                <x-slot:heading>{{ __('Withdraw Competitor') }}</x-slot:heading>
                                                <flux:text>Are you sure you wish to withdraw {{ $row->member->full_name }}?</flux:text>
                                                <x-slot:buttons>
                                                    <flux:button type="submit" variant="danger">{{ __('Withdraw') }}</flux:button>
                                                </x-slot:buttons>
                                            </x-modals.content>
                                        </form>
                                    </flux:modal>
                                @endteleport
                            @endif
                        </div>
                    </flux:table.cell>
                    @foreach ($this->divisionContestants as $col)
                        @php
                            $home_contestant_id = $row->id;
                            $away_contestant_id = $col->id;
                        @endphp

                        @if ($home_contestant_id === $away_contestant_id)
                            <flux:table.cell class="size-28 !min-w-28 bg-gray-500"></flux:table.cell>
                        @else
                            <flux:table.cell class="relative size-28 !min-w-28 border-l">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="flex flex-col items-center @if($row['deleted_at'] || $col['deleted_at']) opacity-50 @endif">
                                        @if ($matrix[$home_contestant_id][$away_contestant_id]['date'] || $matrix[$away_contestant_id][$home_contestant_id]['date'])
                                            <flux:text class="!text-xs">{{ $matrix[$home_contestant_id][$away_contestant_id]['formatted_date'] }}</flux:text>
                                            <flux:heading size="xl">
                                                {{ $matrix[$home_contestant_id][$away_contestant_id]['original_for'] }}-{{ $matrix[$away_contestant_id][$home_contestant_id]['original_for'] }}
                                            </flux:heading>
                                            <flux:text class="!text-xs">{{ $matrix[$home_contestant_id][$away_contestant_id]['formatted_time'] }}</flux:text>
                                        @endif
                                    </div>
                                </div>

                                <div
                                    x-data="{
                                        hasErrors: '',
                                        originalScores: {
                                            score1: $wire.matrix[{{ $home_contestant_id }}][{{ $away_contestant_id }}]['for'],
                                            score2: $wire.matrix[{{ $away_contestant_id }}][{{ $home_contestant_id }}]['for']
                                        }
                                    }"
                                    x-init="$nextTick(() => { hasErrors = {{ json_encode($errors->isNotEmpty()) }} })"
                                    class="absolute top-0.5 bottom-0 right-0.5"
                                >
                                    @if (is_null($session->processed_at))
                                        <flux:modal.trigger name="edit-result-{{ $home_contestant_id }}-{{ $away_contestant_id }}">
                                            <flux:button
                                                x-on:click="$js.openModal({{ $home_contestant_id }}, {{ $away_contestant_id }})"
                                                icon="square-pen"
                                                icon:variant="outline"
                                                square
                                                variant="subtle"
                                                size="xs"
                                            />
                                        </flux:modal.trigger>
                                    @endif

                                    @teleport('body')
                                        <flux:modal
                                            name="edit-result-{{ $home_contestant_id }}-{{ $away_contestant_id }}"
                                            class="modal"
                                            x-on:close="
                                                if (hasErrors) {
                                                    $wire.matrix[{{ $home_contestant_id }}][{{ $away_contestant_id }}]['for'] = originalScores.score1;
                                                    $wire.matrix[{{ $away_contestant_id }}][{{ $home_contestant_id }}]['for'] = originalScores.score2;
                                                }
                                            "
                                        >
                                            <form wire:submit="save({{ $home_contestant_id }}, {{ $away_contestant_id }})" class="space-y-6">
                                                <x-modals.content>
                                                    <x-slot:heading>{{ __('Submit Result') }}</x-slot:heading>

                                                    <div>
                                                        <div class="flex items-center justify-center gap-2">
                                                            <flux:field>
                                                                <flux:label>{{ __('Match Date') }}</flux:label>
                                                                <flux:date-picker
                                                                    wire:model="matrix.{{ $home_contestant_id }}.{{ $away_contestant_id }}.date"
                                                                    :min="$minDate"
                                                                    :max="$maxDate"
                                                                    clearable
                                                                    x-ref="error-border"
                                                                />
                                                            </flux:field>
                                                            <flux:field>
                                                                <flux:label badge="24hr">{{ __('Time') }}</flux:label>
                                                                <flux:input x-ref="error-border" type="time" wire:model="matrix.{{ $home_contestant_id }}.{{ $away_contestant_id }}.time" />
                                                            </flux:field>
                                                        </div>
                                                        @php
                                                            $dateKey = "matrix.$home_contestant_id.$away_contestant_id.date";
                                                            $timeKey = "matrix.$home_contestant_id.$away_contestant_id.time";
                                                            $dateErr = $errors->first($dateKey);
                                                            $timeErr = $errors->first($timeKey);
                                                            $msg = $dateErr ?: $timeErr;  // prefer date error, else time
                                                        @endphp

                                                        @if ($msg)
                                                            <div class="text-center">
                                                                <flux:error x-ref="error" :message="$msg" />
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="space-y-2 mt-6">
                                                        <div class="text-center text-gray-500 text-sm">Best of {{ $league->best_of }} {{ Str::lower($league->tallyUnit->name) }}</div>
                                                        <div class="flex justify-center">
                                                            <div class="inline-block">
                                                                <div class="pb-0.5 flex items-center justify-end">
                                                                    <div class="flex items-center justify-end">
                                                                        <div class="text-right cursor-pointer">
                                                                            <div class="leading-snug font-medium">
                                                                                <flux:heading>{{ $row->member->full_name }}</flux:heading>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="ml-2">
                                                                        <flux:select x-ref="error-border" wire:model="matrix.{{ $home_contestant_id }}.{{ $away_contestant_id }}.for">
                                                                            <flux:select.option value="">-</flux:select.option>
                                                                            @for ($i = 0; $i <= $this->maxTally; $i++ )
                                                                                <flux:select.option value="{{ $i }}">{{ $i }}</flux:select.option>
                                                                            @endfor
                                                                        </flux:select>
                                                                    </div>
                                                                </div>
                                                                <div class="pt-0.5 flex items-center flex-1 justify-end">
                                                                    <div class="flex items-center justify-end mr-2">
                                                                        <div class="left-right cursor-pointer">
                                                                            <div class="leading-snug font-medium">
                                                                                <flux:heading class="text-right sm:text-left">{{ $col->member->full_name }}</flux:heading>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="">
                                                                        <flux:select x-ref="error-border" wire:model="matrix.{{ $away_contestant_id }}.{{ $home_contestant_id }}.for">
                                                                            <flux:select.option value="">-</flux:select.option>
                                                                            @for ($i = 0; $i <= $this->maxTally; $i++ )
                                                                                <flux:select.option value="{{ $i }}">{{ $i }}</flux:select.option>
                                                                            @endfor
                                                                        </flux:select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div x-ref="error">
                                                            @if ($errors->has('matrix.*.*.for') || $errors->has('matrix'))
                                                                <flux:error message="{{ __('Invalid Score') }}" class="text-center" />
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <x-slot:buttons>
                                                        <flux:button wire:click="delete({{ $home_contestant_id }}, {{ $away_contestant_id }})" type="button" variant="danger">{{ __('Delete') }}</flux:button>
                                                        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                                                    </x-slot:buttons>
                                                </x-modals.content>
                                            </form>
                                        </flux:modal>
                                    @endteleport
                                </div>
                            </flux:table.cell>
                        @endif
                    @endforeach
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div wire:loading class="absolute inset-0 z-20 bg-white -my-3.5 opacity-50"></div>

</div>

@script
<script>
    $js('openModal', (home_contestant_id, away_contestant_id) => {
        document.querySelectorAll('[x-ref="error"]').forEach(el => {
            el.classList.add('hidden');
        });
        document.querySelectorAll('[x-ref="error-border"]').forEach(el => {
            el.classList.remove('border-red-500');
            button = el.children[0];
            if (button) {
                button.classList.remove('border-red-500');
            }
        });
        $wire.matrix[home_contestant_id][away_contestant_id].for = $wire.matrix[home_contestant_id][away_contestant_id].original_for
        $wire.matrix[home_contestant_id][away_contestant_id].date = $wire.matrix[home_contestant_id][away_contestant_id].original_date
        $wire.matrix[home_contestant_id][away_contestant_id].time = $wire.matrix[home_contestant_id][away_contestant_id].original_time
        $wire.matrix[away_contestant_id][home_contestant_id].for = $wire.matrix[away_contestant_id][home_contestant_id].original_for
        $wire.matrix[away_contestant_id][home_contestant_id].date = $wire.matrix[away_contestant_id][home_contestant_id].original_date
        $wire.matrix[away_contestant_id][home_contestant_id].time = $wire.matrix[away_contestant_id][home_contestant_id].original_time
    })
</script>
@endscript