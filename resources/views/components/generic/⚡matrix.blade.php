<?php

use Flux\Flux;
use Carbon\Carbon;
use App\Models\Club;
use App\Models\League;
use App\Models\Player;
use App\Models\Result;
use App\Models\Session;
use Livewire\Component;
use App\Models\Division;
use App\Models\Contestant;
use Livewire\Attributes\On;
use App\Rules\ValidateScore;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;
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

    public ?array $selectedPlayer = [
        'id' => null
    ];

    public array $matrix = [];

    public int $promoteCount;
    public int $relegateCount;

    public ?int $editingHomeId = null;
    public ?int $editingAwayId = null;

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
        $this->matrix = $this->buildMatrix();
        $this->promoteCount = $this->division->promote_count;
        $this->relegateCount = $this->division->relegate_count;
    }

    #[Renderless]
    public function updatedPromoteCount()
    {
        $this->division->update([
            'promote_count' => $this->promoteCount,
        ]);

        $this->dispatch('division-movement-updated');

        flux::toast(
            variant: 'success',
            text: 'Promote count updated.'
        );
    }

    #[Renderless]
    public function updatedRelegateCount()
    {
        $this->division->update([
            'relegate_count' => $this->relegateCount,
        ]);

        $this->dispatch('division-movement-updated');

        flux::toast(
            variant: 'success',
            text: 'Relegate count updated.'
        );
    }

    #[Computed]
    public function availablePlayers()
    {
        $excludedPlayerIds = $this->session->playersInSession();

        return $this->club->players()
            ->withPivot('club_player_id')
            ->withExists('users')
            ->whereNotIn('players.id', $excludedPlayerIds)
            ->orderByName()
            ->get();
    }

    protected function resetDivision()
    {
        $clubId = $this->club->id;

        $this->division = $this->division->load([
            'contestants' => fn ($q) => $q->orderBy('index')
                ->withTrashed()
                ->with([
                    'player' => fn ($q) => $q->with([
                        'clubs' => fn ($q) => $q->whereKey($clubId),
                    ]),
                ]),
            'results' => fn ($q) => $q->with(['homePlayer', 'awayPlayer']),
        ]);
    }

    #[Computed]
    public function divisionContestants()
    {
        return $this->division->contestants()
            ->withTrashed()
            ->orderBy('index')
            ->with([
                'player' => fn ($q) => $q
                    ->with([
                        'clubs' => fn ($q) => $q->whereKey($this->club->id)
                    ])
                    ->withExists('users')
                    ->with('users')
            ])
            ->get();
    }

    protected function buildMatrix(): array
    {
        $matrix = [];

        // We key by CONTESTANT IDs to match the UI and openEdit/save
        $contestants = $this->division->contestants()
            ->withTrashed()
            ->get([
                'id',
                'player_id'
            ]);

        $ids = $contestants->pluck('id')->all();

        // Map player_id -> contestant_id (for this division)
        $playerToContestant = $contestants->pluck('id', 'player_id'); // [player_id => contestant_id]

        $empty = [
            'original_for'      => null,
            'for'               => null,
            'points'            => 0,
            'original_attended' => false,
            'attended'          => false,
            'original_date'     => null,
            'date'              => null,
            'formatted_date'    => null,
            'original_time'     => null,
            'time'              => null,
            'formatted_time'    => null,
        ];

        foreach ($ids as $r) {
            $matrix[$r] = [];
            foreach ($ids as $c) {
                if ($r === $c) continue;
                $matrix[$r][$c] = $empty;
            }
        }

        $s = $this->session;

        foreach ($this->division->results as $res) {
            // Results store PLAYER ids. Translate to CONTESTANT ids for this division.
            $hPlayer = $res->home_player_id;
            $aPlayer = $res->away_player_id;

            $h = $playerToContestant[$hPlayer] ?? null;
            $a = $playerToContestant[$aPlayer] ?? null;

            if (!$h || !$a || !isset($matrix[$h][$a])) {
                // result points to players not currently in this division grid
                continue;
            }

            $dt = $res->match_at->timezone($this->session->timezone);

            $cellData = [
                'original_date'  => $dt->format('Y-m-d'),
                'date'           => $dt->format('Y-m-d'),
                'formatted_date' => $dt->format('j M Y'),
                'original_time'  => $dt->format('H:i'),
                'time'           => $dt->format('H:i'),
                'formatted_time' => $dt->format('g:iA'),
            ];

            // Home (h vs a)
            $matrix[$h][$a]['original_for']      = $res->home_score;
            $matrix[$h][$a]['for']               = $res->home_score;
            $matrix[$h][$a]['points']            = $this->calculatePoints($res->home_score, $res->away_score, $res->home_attended, $s);
            $matrix[$h][$a]['original_attended'] = !$res->home_attended;
            $matrix[$h][$a]['attended']          = !$res->home_attended;
            $matrix[$h][$a] = array_merge($matrix[$h][$a], $cellData);

            // Away (a vs h)
            $matrix[$a][$h]['original_for']      = $res->away_score;
            $matrix[$a][$h]['for']               = $res->away_score;
            $matrix[$a][$h]['points']            = $this->calculatePoints($res->away_score, $res->home_score, $res->away_attended, $s);
            $matrix[$a][$h]['original_attended'] = !$res->away_attended;
            $matrix[$a][$h]['attended']          = !$res->away_attended;
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
        // Derive player ids for robust deletion no matter which FK is set
        $homePlayerId = $this->division->contestants()->withTrashed()->findOrFail($home_contestant_id)->player_id;
        $awayPlayerId = $this->division->contestants()->withTrashed()->findOrFail($away_contestant_id)->player_id;

        $this->division->results()
            // newer rows (by player)
            ->whereIn('home_player_id', [$homePlayerId, $awayPlayerId])
            ->whereIn('away_player_id', [$homePlayerId, $awayPlayerId])
            ->delete();

        // legacy rows (by contestant) if they still exist
        $this->division->results()
            ->whereIn('home_contestant_id', [$home_contestant_id, $away_contestant_id])
            ->whereIn('away_contestant_id', [$home_contestant_id, $away_contestant_id])
            ->delete();

        $this->resetDivision();
        $this->matrix = $this->buildMatrix();

        Flux::modal('edit-result')->close();

        $this->editingHomeId = null;
        $this->editingAwayId = null;


        flux::toast(
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

        $homePlayerId = $this->division->contestants()->find($homeId)->player_id;
        $awayPlayerId = $this->division->contestants()->find($awayId)->player_id;

        Result::updateOrCreate(
            [
                'club_id' => $this->club->id,
                'league_id' => $this->league->id,
                'league_session_id' => $this->session->id,
                'division_id'        => $this->division->id,
                'home_player_id' => $homePlayerId,
                'away_player_id' => $awayPlayerId,
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

        flux::toast(
            variant: 'success',
            text: 'Result saved.'
        );

        $this->editingHomeId = null;
        $this->editingAwayId = null;
    }

    public function addPlayer()
    {
        DB::transaction(function () {
            $nextIndex = ($this->divisionContestants->max('index') ?? -1) + 1;

            $contestant = $this->division->contestants()->create([
                'league_session_id' => $this->session->id,
                'division_id'       => $this->division->id,
                // no more member_id – contestant is tied directly to a player
                'player_id'         => $this->selectedPlayer['id'],
                'index'             => $nextIndex,
            ]);

            $this->division->increment('contestant_count');

            // Get a user from the player (via player_user pivot)
            $user = $contestant->player->users()->first();

            if ($user && $this->session->isPublished()) {
                $contestant->notify();
                Mail::to($user->email)
                    ->queue(new AddedToLeagueSessionEmail($this->club, $this->league, $this->session, $user));
            }
        });

        $this->resetDivision();

        // 🔑 force recompute of computed collections
        unset($this->divisionContestants, $this->availablePlayers);

        $this->selectedPlayer['id'] = null;

        $this->matrix = $this->buildMatrix();

        Flux::modals()->close('add-player');

        $this->dispatch('competitor-added');

        flux::toast(
            variant: 'success',
            text: 'Player added.'
        );
    }

    public function withdraw(int $contestantId)
    {
        $contestant = $this->division->contestants()->findOrFail($contestantId);

        $contestant->delete();

        $this->resetDivision();

        Flux::modals()->close();

        unset($this->divisionContestants, $this->availablePlayers);

        flux::toast(
            variant: 'success',
            text: 'Player withdrawn.'
        );
    }

    public function reinstate(int $contestantId)
    {
        $contestant = $this->division->contestants()->findOrFail($contestantId);

        $contestant->restore();

        $this->resetDivision();

        Flux::modals()->close();

        unset($this->divisionContestants, $this->availablePlayers);

        flux::toast(
            variant: 'success',
            text: 'Player reinstated.'
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

        $this->matrix = $this->buildMatrix();

        $this->dispatch('competitor-removed');

        unset($this->divisionContestants, $this->availablePlayers);

        flux::toast(
            variant: 'success',
            text: 'Player removed.'
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

    private function indexToLetterUpper(int $i): string
    {
        return chr(ord('A') + $i);
    }
};
?>

<x-ui.cards.mobile class="relative">

    @php
        $showPromote = $division->tier->index > 0;
        $showRelegate = $division->tier->index + 1 !== $division->session->tiers()->count();
    @endphp

    <div class="relative space-y-8">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ $this->division->name() }}</flux:heading>

            @if (is_null($session->processed_at))
                <div>
                    <flux:modal.trigger name="add-player">
                        <flux:button
                            variant="primary"
                            icon="plus"
                        >
                            Competitor
                        </flux:button>
                    </flux:modal.trigger>

                    @teleport('body')
                        <flux:modal name="add-player" class="modal">
                            <form wire:submit="addPlayer">
                                <x-modals.content>
                                    <x-slot:heading>{{ __('Add Member') }}</x-slot:heading>
                                    @if (count($this->availablePlayers) === 0)
                                        <flux:callout variant="secondary" icon="information-circle">
                                            {{ __('All members are competing in this league session.') }}
                                        </flux:callout>
                                    @else
                                        <flux:text>Select the member you wish to add to this division.</flux:text>
                                        <flux:field>
                                            <flux:select variant="listbox" wire:model="selectedPlayer.id" searchable clearable placeholder="Select member...">
                                                @foreach ($this->availablePlayers as $member)
                                                    <flux:select.option value="{{ $member->id }}">
                                                        <x-generic.member :$club :$member :memberId="$member->pivot->club_player_id" :isLink="false" />
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </flux:field>
                                        <x-slot:buttons>
                                            <flux:button
                                                type="submit"
                                                variant="primary"
                                                x-bind:disabled="!$wire.selectedPlayer.id"
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

        @if ($showPromote || $showRelegate)
            <div class="flex flex-col items-center">
                <div class="flex items-center justify-center gap-6 min-h-10">
                    @if ($showPromote)
                        <div class="flex flex-col items-end">
                            <div class="flex items-center gap-1">
                                <flux:label>Promote</flux:label>
                                <flux:icon.arrow-up variant="micro" class="size-5 text-green-600" />
                                @if (is_null($session->processed_at))
                                    <flux:select
                                        wire:model.live="promoteCount"
                                        @class([
                                            'cursor-not-allowed' => $session->processed_at
                                        ])
                                        :disabled="! is_null($session->processed_at)"
                                    >
                                        @foreach (range(0, $division->contestant_count - $division->relegate_count) as $i)
                                            <flux:select.option>{{ $i }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @else
                                    <flux:heading class="!text-green-600">{{ $division->promote_count }}</flux:heading>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($showRelegate)
                        <div class="flex flex-col items-end">
                            <div class="flex items-center gap-1">
                                <flux:label>Relegate</flux:label>
                                <flux:icon.arrow-down variant="mini" class="size-5 text-red-600" />
                                @if (is_null($session->processed_at))
                                    <flux:select
                                        wire:model.live="relegateCount"
                                        @class([
                                            'cursor-not-allowed' => $session->processed_at
                                        ])
                                        :disabled="! is_null($session->processed_at)"
                                    >
                                        @foreach (range(0, $division->contestant_count - $division->promote_count) as $i)
                                            <flux:select.option>{{ $i }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @else
                                    <flux:heading class="!text-red-600">{{ $division->relegate_count }}</flux:heading>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
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

        <flux:accordion variant="reverse" class="mt-10">
            <!-- Table -->
            <flux:accordion.item expanded>
                <flux:accordion.heading class="my-3" size="lg">Table</flux:accordion.heading>

                <flux:accordion.content>
                    <div class="pt-2 pb-4">
                        <x-ui.cards.table>
                            <flux:table.columns class="bg-stone-50">
                                <flux:table.column class="w-0"></flux:table.column>
                                <flux:table.column sticky></flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">P</flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">W</flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">D</flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">L</flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">F</flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">A</flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">+/-</flux:table.column>
                                <flux:table.column class="w-[49px]" align="center">Pts</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows class="!divide-y-0">
                                <!-- Contestants -->
                                @php
                                    $standings = $division->calculateStandings()['standings'];
                                @endphp

                                @foreach ($standings as $i => $contestant)
                                    @php
                                        $curr = $contestant['rank'];
                                        $prev = $standings[$i-1]['rank'] ?? null;
                                        $next = $standings[$i+1]['rank'] ?? null;
                                        $isTie = ($prev === $curr) || ($next === $curr);
                                    @endphp
                                    @if ($loop->index === $loop->count - $division->relegate_count && $loop->index !== $loop->count)
                                        <flux:table.row>
                                            <flux:table.cell colspan="10" class="!py-0 h-0.5 bg-red-500"></flux:table.cell>
                                        </flux:table.row>
                                    @endif
                                    <flux:table.row wire:key="{{ $contestant['id'] }}">
                                        <flux:table.cell style="height: 49px;">{{ $curr }}@if($isTie) =@endif</flux:table.cell>
                                        <flux:table.cell>
                                            <x-generic.table-contestant :$club :$contestant />
                                        </flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['played'] }}</flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['won'] }}</flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['drawn'] }}</flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['lost'] }}</flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['for'] }}</flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['against'] }}</flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['diff'] }}</flux:table.cell>
                                        <flux:table.cell align="center" class="!text-zinc-900">{{ $contestant['points'] }}</flux:table.cell>
                                    </flux:table.row>
                                    @if ($loop->iteration !== $loop->count - $division->relegate_count && $loop->iteration !== $loop->count && $loop->iteration !== $division->promote_count)
                                        <!-- <flux:table.row>
                                            <flux:table.cell colspan="10" class="!py-0 border-t"></flux:table.cell>
                                        </flux:table.row> -->
                                    @endif
                                    @if ($loop->iteration === $division->promote_count)
                                        <flux:table.row>
                                            <flux:table.cell colspan="10" class="!py-0 h-0.5 bg-green-500"></flux:table.cell>
                                        </flux:table.row>
                                    @endif
                                @endforeach
                            </flux:table.rows>
                        </x-ui.cards.table>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <!-- Results -->
            <flux:accordion.item expanded>
                <flux:accordion.heading class="my-3" size="lg">Results</flux:accordion.heading>

                <flux:accordion.content>
                    <div class="pt-2">
                        <x-ui.cards.table>
                            <flux:table.columns class="bg-zinc-100">
                                <flux:table.column class="w-full"></flux:table.column>
                                <flux:table.column class="border-l min-w-[45px]"></flux:table.column>
                                @foreach ($this->divisionContestants as $index => $col)
                                    <flux:table.column wire:key="{{ $col['id'] }}" class="border-l min-w-[50px]" align="center">
                                        <div class="flex flex-col items-center">
                                            <div class="flex flex-col items-center">
                                                {{ $this->indexToLetterUpper($index) }}
                                            </div>
                                        </div>
                                    </flux:table.column>
                                @endforeach
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->divisionContestants as $index => $row)
                                    <flux:table.row wire:key="{{ $row['id'] }}">
                                        <flux:table.cell style="height: 49px;">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex-1 flex items-center justify-between gap-2">
                                                    <div>
                                                        <flux:heading class="flex items-center gap-1">
                                                            <x-generic.member
                                                                :$club
                                                                :member="$row->player"
                                                                memberId="{{ $row->player->clubs()->first()->pivot->club_player_id }}"
                                                                :showUser="true"
                                                                :showTelNo="false"
                                                                :isLink="true"
                                                            />
                                                        </flux:heading>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    @if ($row['deleted_at'])
                                                        <flux:badge color="red" size="sm" class="ml-2">WD</flux:badge>
                                                    @endif

                                                    @if (is_null($session->processed_at))
                                                        <flux:dropdown
                                                            position="bottom"
                                                            align="end"
                                                        >
                                                            <flux:button
                                                                variant="subtle"
                                                                icon="ellipsis-vertical"
                                                                size="xs"
                                                            />

                                                            <flux:menu>
                                                                @if ($row['deleted_at'])
                                                                    <flux:menu.item
                                                                        wire:click="reinstate({{ $row['id'] }})"
                                                                        icon="circle-plus"
                                                                    >
                                                                        Reinstate
                                                                    </flux:menu.item>
                                                                @else
                                                                    <flux:menu.item
                                                                        wire:click="withdraw({{ $row['id'] }})"
                                                                        icon="circle-minus"
                                                                    >
                                                                        Withdraw
                                                                    </flux:menu.item>
                                                                @endif

                                                                @if ($division->contestant_count > 1)
                                                                    <flux:menu.separator />

                                                                    <flux:modal.trigger name="matrix-delete-{{ $row->id }}">
                                                                        <flux:menu.item
                                                                            variant="danger"
                                                                            icon="trash"
                                                                        >
                                                                            Delete
                                                                        </flux:menu.item>
                                                                    </flux:modal.trigger>

                                                                    @teleport('body')
                                                                        <flux:modal name="matrix-delete-{{ $row->id }}" class="modal">
                                                                            <form wire:submit="removeContestant({{ $row->id }})">
                                                                                <x-modals.content>
                                                                                    <x-slot:heading>{{ __('Remove Competitor') }}</x-slot:heading>
                                                                                    <flux:text>Are you sure you wish to remove {{ $row->player->name }} from this box?</flux:text>
                                                                                    <flux:text>All results involving them will be permanently deleted!</flux:text>
                                                                                    <x-slot:buttons>
                                                                                        <flux:button type="submit" variant="danger">{{ __('Remove') }}</flux:button>
                                                                                    </x-slot:buttons>
                                                                                </x-modals.content>
                                                                            </form>
                                                                        </flux:modal>
                                                                    @endteleport
                                                                @endif
                                                            </flux:menu>
                                                        </flux:dropdown>
                                                    @endif

                                                </div>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell align="center" class="border-l bg-zinc-100">
                                            <div class="font-medium text-gray-900">{{ $this->indexToLetterUpper($index) }}</div>
                                        </flux:table.cell>
                                        @foreach ($this->divisionContestants as $col)
                                            <flux:table.cell
                                                wire:key="cell-{{ $row->id }}-{{ $col->id }}"
                                                class="relative border-l"
                                            >
                                                @if ($row->id !== $col->id)
                                                    <div class="absolute inset-0 flex items-center justify-center">
                                                        <div
                                                            @class([
                                                                'absolute inset-0 flex flex-col items-center justify-center',
                                                                'opacity-50 bg-red-50' => $col['deleted_at'] || $row['deleted_at']
                                                            ])
                                                        >
                                                            @if ($matrix[$row->id][$col->id]['original_for'] || $matrix[$col->id][$row->id]['original_for'])
                                                                <div
                                                                    @class([
                                                                        'flex items-center text-zinc-900',
                                                                    ])
                                                                >
                                                                    @if ($matrix[$row->id][$col->id]['original_attended'])
                                                                        <span>!</span>
                                                                    @endif
                                                                    <span>{{ $matrix[$row->id][$col->id]['original_for'] }}-{{ $matrix[$col->id][$row->id]['original_for'] }}</span>
                                                                    @if ($matrix[$col->id][$row->id]['original_attended'])
                                                                        <span>!</span>
                                                                    @endif
                                                                </div>
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
                                                                        variant="micro"
                                                                        class="size-4 text-blue-300"
                                                                    />
                                                                </div>
                                                            @else
                                                                <div class="absolute top-0.5 right-0.5">
                                                                    <flux:icon.pencil-square
                                                                        variant="micro"
                                                                        class="size-3 text-blue-300"
                                                                    />
                                                                </div>
                                                            @endif
                                                            <button
                                                                type="button"
                                                                class="absolute inset-0 hover:bg-yellow-400 opacity-10"
                                                                x-on:click.prevent="
                                                                    $dispatch('edit-result:open', {
                                                                        home: @js($row->id),
                                                                        away: @js($col->id),
                                                                        homeName: @js($row->player->name),
                                                                        awayName: @js($col->player->name),

                                                                        // scores from your Livewire-built matrix
                                                                        homeScore: @js($matrix[$row->id][$col->id]['for']),
                                                                        awayScore: @js($matrix[$col->id][$row->id]['for']),

                                                                        // optional extra fields
                                                                        date: @js($matrix[$row->id][$col->id]['date']),
                                                                        time: @js($matrix[$row->id][$col->id]['time']),
                                                                        homeNoShow: @js($matrix[$row->id][$col->id]['attended']),
                                                                        awayNoShow: @js($matrix[$col->id][$row->id]['attended']),
                                                                    })
                                                                "
                                                            ></button>

                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="absolute inset-0 bg-zinc-200"></div>
                                                @endif
                                            </flux:table.cell>
                                        @endforeach
                                    </flux:table.row>
                                @endforeach

                                @teleport('body')
                                    <flux:modal
                                        name="edit-result"
                                        class="modal"
                                        x-data="{
                                            hasErrors: false,
                                            open: false,
                                            loading: false,
                                            home: null,
                                            away: null,
                                            homeName: '',
                                            awayName: '',

                                            homeScore: null,
                                            awayScore: null,
                                            date: null,
                                            time: null,
                                            homeNoShow: false,
                                            awayNoShow: false,

                                            init() {
                                                // when a cell requests the modal
                                                window.addEventListener('edit-result:open', async (e) => {
                                                    this.hasErrors = false;
                                                    this.home = e.detail.home
                                                    this.away = e.detail.away
                                                    this.homeName = e.detail.homeName
                                                    this.awayName = e.detail.awayName

                                                    this.homeScore = e.detail.homeScore
                                                    this.awayScore = e.detail.awayScore
                                                    this.date = e.detail.date
                                                    this.time = e.detail.time
                                                    this.homeNoShow = !!e.detail.homeNoShow
                                                    this.awayNoShow = !!e.detail.awayNoShow

                                                    console.log(this.homeName, this.awayName)

                                                    // 1) open immediately
                                                    this.open = true
                                                    this.loading = true
                                                    $flux.modal('edit-result').show()

                                                    // 2) now fetch/populate via Livewire
                                                    await $wire.openEdit(this.home, this.away)

                                                    // 3) swap skeleton -> form
                                                    this.loading = false
                                                })

                                                // when modal closes, reset state
                                                window.addEventListener('edit-result:close', () => {
                                                    console.log('fired')
                                                    this.open = false
                                                    this.home = null
                                                    this.away = null
                                                    this.loading = false
                                                })
                                            }
                                        }"
                                    >
                                        <!-- Skeleton -->
                                        <template x-if="loading">
                                            <x-modals.content>
                                                <x-slot:heading>{{ __('Submit Result') }}</x-slot:heading>
                                                <flux:skeleton.group animate="pulse">

                                                    <div>
                                                        <div class="flex items-center justify-center gap-2">
                                                            <flux:field>
                                                                <flux:label>{{ __('Match Date') }}</flux:label>
                                                                <flux:skeleton  class="h-10 w-40" />
                                                            </flux:field>
                                                            <flux:field>
                                                                <flux:label>{{ __('Time') }}</flux:label>
                                                                <flux:skeleton  class="h-10 w-[122px]" />
                                                            </flux:field>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2 mt-6">
                                                        <flux:text class="text-center">Best of {{ $league->best_of }} {{ Str::lower($league->tallyUnit->name) }}</flux:text>

                                                        <div class="flex flex-col items-center">
                                                            <!-- HOME -->
                                                            <table>
                                                                <tr>
                                                                    <td class="p-1 pl-0 pr-4">
                                                                        <flux:skeleton  class="h-10 w-8" />
                                                                    </td>
                                                                    <td class="p-1">
                                                                        <flux:skeleton  class="h-10 w-30" />
                                                                    </td>
                                                                    <td class="p-1 pr-0">
                                                                        <flux:skeleton  class="h-10 w-16" />
                                                                    </td>
                                                                </tr>

                                                                <!-- AWAY -->
                                                                <tr>
                                                                    <td class="p-1 pl-0 pr-4">
                                                                        <flux:skeleton  class="h-10 w-8" />
                                                                    </td>
                                                                    <td class="p-1">
                                                                        <flux:skeleton  class="h-10 w-30" />
                                                                    </td>
                                                                    <td class="p-1 pr-0">
                                                                        <flux:skeleton  class="h-10 w-16" />
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </div>

                                                        <div class="flex flex-col items-center">
                                                            <flux:text class="text-center w-80 !text-xs">Tick a box if a player didn't turn up so their opponent can claim the points.</flux:text>
                                                        </div>
                                                    </div>

                                                </flux:skeleton.group>
                                                <x-slot:buttons>
                                                    <flux:skeleton animate="pulse" class="h-10 w-[75px]" />
                                                    <flux:skeleton animate="pulse" class="h-10 w-[65px]" />
                                                </x-slot:buttons>
                                            </x-modals.content>
                                        </template>

                                        <template x-if="!loading">
                                            <div>
                                                @if($editingHomeId && $editingAwayId)
                                                    @php
                                                        $homeId = $editingHomeId;
                                                        $awayId = $editingAwayId;
                                                        $homeContestant = $this->divisionContestants->firstWhere('id', $homeId);
                                                        $awayContestant = $this->divisionContestants->firstWhere('id', $awayId);
                                                    @endphp

                                                    <form x-on:submit.prevent="
                                                        $js.syncToLivewire(home, away, homeScore, awayScore, homeNoShow, awayNoShow, date, time);
                                                        $wire.save(home, away);
                                                    " class="space-y-6">
                                                        <x-modals.content>
                                                            <x-slot:heading>{{ __('Submit Result') }}</x-slot:heading>

                                                            <div>
                                                                <div class="flex items-center justify-center gap-2">
                                                                    <flux:field>
                                                                        <flux:label>{{ __('Match Date') }}</flux:label>
                                                                        <flux:date-picker
                                                                            x-model="date"
                                                                            :min="$minDate"
                                                                            :max="$maxDate"
                                                                        />
                                                                    </flux:field>
                                                                    <flux:field>
                                                                        <flux:label>{{ __('Time') }}</flux:label>
                                                                        <flux:time-picker
                                                                            type="input"
                                                                            x-model="time"
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
                                                                        maxScore: {{ $this->maxTally }},

                                                                        setScores(side) {
                                                                            if (side === 'home' && this.homeNoShow) {
                                                                                this.homeScore = 0;
                                                                                this.awayScore = this.maxScore;
                                                                            } else if (side === 'away' && this.awayNoShow) {
                                                                                this.awayScore = 0;
                                                                                this.homeScore = this.maxScore;
                                                                            }
                                                                        },

                                                                        onHomeToggle() {
                                                                            this.homeNoShow = !this.homeNoShow;
                                                                            if (this.homeNoShow) {
                                                                                this.awayNoShow = false;
                                                                                this.setScores('home');
                                                                            } else {
                                                                                if (!this.awayNoShow) {
                                                                                    this.homeScore = '';
                                                                                    this.awayScore = '';
                                                                                } else {
                                                                                    this.setScores('away');
                                                                                }
                                                                            }
                                                                        },

                                                                        onAwayToggle() {
                                                                            this.awayNoShow = !this.awayNoShow;
                                                                            if (this.awayNoShow) {
                                                                                this.homeNoShow = false;
                                                                                this.setScores('away');
                                                                            } else {
                                                                                if (!this.homeNoShow) {
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
                                                                                    x-bind:checked="homeNoShow"
                                                                                    class="!-mt-px"
                                                                                />
                                                                            </td>
                                                                            <td class="p-1">
                                                                                <flux:heading
                                                                                    class="text-right"
                                                                                    x-bind:class="{ 'line-through text-gray-400': homeNoShow }"
                                                                                >
                                                                                    <div x-text="homeName" />
                                                                                </flux:heading>
                                                                            </td>
                                                                            <td class="p-1 pr-0">
                                                                                <flux:select
                                                                                    x-bind:disabled="homeNoShow || awayNoShow"
                                                                                    x-model="homeScore"
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
                                                                                    x-bind:checked="awayNoShow"
                                                                                    class="!-mt-px"
                                                                                />
                                                                            </td>
                                                                            <td class="p-1">
                                                                                <flux:heading
                                                                                    class="text-right"
                                                                                    x-bind:class="{ 'line-through text-gray-400': awayNoShow }"
                                                                                >
                                                                                    <div x-text="awayName" />
                                                                                </flux:heading>
                                                                            </td>
                                                                            <td class="p-1 pr-0">
                                                                                <flux:select
                                                                                    x-bind:disabled="homeNoShow || awayNoShow"
                                                                                    x-model="awayScore"
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
                                                                    x-on:click="$wire.delete(home, away)"
                                                                    type="button"
                                                                    variant="danger"
                                                                >
                                                                    {{ __('Delete') }}
                                                                </flux:button>
                                                                <flux:button
                                                                    type="submit"
                                                                    variant="primary"
                                                                >
                                                                    {{ __('Save') }}
                                                                </flux:button>
                                                            </x-slot:buttons>
                                                        </x-modals.content>
                                                    </form>
                                                @endif
                                            </div>
                                        </template>
                                    </flux:modal>
                                @endteleport

                            </flux:table.rows>
                        </x-ui.cards.table>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </div>
</x-ui.cards.mobile>

<script>
    this.$js.syncToLivewire = (home, away, homeScore, awayScore, homeNoShow, awayNoShow, date, time) => {
        console.log(homeScore)
        // write Alpine -> Livewire matrix
        $wire.set(`matrix.${home}.${away}.for`, homeScore)
        $wire.set(`matrix.${away}.${home}.for`, awayScore)

        $wire.set(`matrix.${home}.${away}.date`, date)
        $wire.set(`matrix.${home}.${away}.time`, time)

        $wire.set(`matrix.${home}.${away}.attended`, homeNoShow)
        $wire.set(`matrix.${away}.${home}.attended`, awayNoShow)

        console.log(homeScore)
    }

    document.addEventListener('DOMContentLoaded', () => {
        Livewire.on('toast', ({ variant, text }) => {
            // Flux global helper
            $flux.toast({ variant, text })
        })
    })
</script>
