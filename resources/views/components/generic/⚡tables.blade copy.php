<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use App\Traits\Helpers;
use Livewire\Component;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use App\Livewire\Forms\SessionRulesForm;
use App\Jobs\SendLeagueSessionPublishedMessage;

new class extends Component
{
    use Helpers;

    public Club $club;

    public League $league;

    public Session $session;

    public bool $updateComponent = false;

    public SessionRulesForm $form;

    public array $tierNames = [];

    public function mount()
    {
        $this->form->setSession($this->session);

        $this->tierNames = $this->session->tiers()
            ->orderBy('index')
            ->pluck('name', 'id')
            ->toArray();
    }

    #[On('date-updated')]
    public function refreshTables()
    {

    }

    public function updateTierName(int $tierId)
    {
        $this->validate([
            "tierNames.$tierId" => 'required|string|max:20',
        ], [
            "tierNames.$tierId.max" => 'Must not be more than 20 characters.'
        ]);

        $tier = $this->session->tiers()->where('id', $tierId)->firstOrFail();

        $tier->update([
            'name' => $this->tierNames[$tierId],
        ]);

        Flux::modals()->close("update-tier-name-{$tierId}");

        Flux::toast(
            variant: 'success',
            text: 'Tier ' . ($tier->index + 1) . ' Name updated.'
        );
    }

    public function publish()
    {
        abort_if($this->session->isPublished(), 404);

        DB::transaction(function () {
            $this->session->update([
                'published_at' => now(),
            ]);

            $this->dispatch('update-status');

            Flux::toast(
                variant: 'success',
                text: 'Session live.'
            );

            if (! $this->session->ends_at->isPast()) {
                SendLeagueSessionPublishedMessage::dispatch($this->session);
            }

            Flux::modals()->close('publish-session');
        });
    }

    public function unpublish()
    {
        $this->session->update([
            'published_at' => null,
        ]);

        $this->dispatch('update-status');

        Flux::toast(
            variant: 'success',
            text: 'Session not live.'
        );

        Flux::modals()->close('unpublish-session');
    }

    public function process()
    {
        DB::transaction(function () {
            $tiers = collect($this->session->calculateStandings())->sortBy('index')->values();

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
                $this->session->contestants()
                    ->where('player_id', $contestant['player_id'])
                    ->update([
                        'overall_rank' => $contestant['overall_rank'],
                        'division_rank' => $contestant['division_rank'],
                    ]);
            }

            $this->session->update([
                'processed_at' => now(),
            ]);
        });

        $this->dispatch('update-status');

        Flux::toast(
            variant: 'success',
            text: 'Session processed.'
        );

        Flux::modals()->close('process-session');

        // $this->redirectRoute('club.admin.leagues.sessions.tables', ['club' => $this->club, 'league' => $this->league, 'session' => $this->session], navigate: true);
    }

    public function unprocess()
    {
        DB::transaction(function () {
            $this->session->update([
                'processed_at' => null,
            ]);

            $this->session->contestants()->update([
                'overall_rank' => null,
                'division_rank' => null,
            ]);
        });

        Flux::toast(
            variant: 'success',
            text: 'Session unprocessed.'
        );

        Flux::modals()->close('unprocess-session');

        $this->dispatch('update-status');
    }

    public function updatedForm()
    {
        $this->form->update();

        $this->updateComponent = true;

        $this->session = $this->session->fresh();

        Flux::modals()->close('edit-points');

        $this->dispatch('rules-updated');

        $this->updateComponent = false;

        Flux::toast(
            variant: 'success',
            text: 'Table Points Awarded For... updated.'
        );

    }

    #[On('update-status')]
    public function updateStatus()
    { }

    public function with(): array
    {
        return [
            'tallyUnit' => Str::of($this->league->tallyUnit->name)->singular(),
            'tiers' => $this->session->calculateStandings(),
            'previousSession' => $this->session->previous(),
            'nextSession' => $this->session->next(),
        ];
    }
}; ?>

<div class="relative space-y-main">

    @php
        $isLatestSession = $session->id === $league->latestSession()->first()->id;
    @endphp
    <div class="flex items-center justify-between h-10">
        <x-headings.page-heading>Tables</x-headings.page-heading>
        @if (is_null($session->processed_at) && $isLatestSession)
            <div class="min-h-10">
                <livewire:buttons.destroy-tables :$club :$league :$session />
            </div>
        @endif
    </div>
    <div class="space-y-12">
        <div class="flex flex-col-reverse md:flex-row gap-6">
            <div class="md:flex-1">
                <flux:card class="space-y-6">
                    <flux:accordion>
                        <flux:accordion.item expanded>
                            <flux:accordion.heading class="mb-1.5">
                                <flux:heading size="lg">Table Points Awarded For..</flux:heading>
                            </flux:accordion.heading>

                            <flux:accordion.content class="space-y-4 my-3">
                                <div class="space-y-4">
                                    <form wire:submit="save">
                                        <div class="grid grid-cols-2 xl:grid-cols-4 gap-6">
                                            <div>
                                                <flux:field>
                                                    @if (is_null($session->processed_at))
                                                        <flux:label>Winning</flux:label>
                                                        <flux:select
                                                            wire:model.live="form.pts_win"
                                                            variant="listbox"
                                                            :disabled="! is_null($session->processed_at)"
                                                        >
                                                            @for ($i = 0; $i <= 3; $i++)
                                                                <flux:select.option>{{ $i }}</flux:select.option>
                                                            @endfor
                                                        </flux:select>
                                                    @else
                                                        <flux:description>Winning</flux:description>
                                                        <flux:heading class="mt-2">{{ $session->pts_win }}</flux:heading>
                                                    @endif
                                                </flux:field>
                                            </div>
                                            <div>
                                                <flux:field>
                                                    @if (is_null($session->processed_at))
                                                        <flux:label>Drawing</flux:label>
                                                        <flux:select
                                                            wire:model.live="form.pts_draw"
                                                            variant="listbox"
                                                            :disabled="! is_null($session->processed_at)"
                                                        >
                                                            @for ($i = 0; $i <= 1; $i++)
                                                                <flux:select.option>{{ $i }}</flux:select.option>
                                                            @endfor
                                                        </flux:select>
                                                    @else
                                                        <flux:description>Drawing</flux:description>
                                                        <flux:heading class="mt-2">{{ $session->pts_draw }}</flux:heading>
                                                    @endif
                                                </flux:field>
                                            </div>
                                            <div>
                                                <flux:field>
                                                    @if (is_null($session->processed_at))
                                                        <flux:label>Each {{ Str::title($tallyUnit) }} Won</flux:label>
                                                        <flux:select
                                                            wire:model.live="form.pts_per_set"
                                                            variant="listbox"
                                                            :disabled="! is_null($session->processed_at)"
                                                        >
                                                            @for ($i = 0; $i <= 1; $i++)
                                                                <flux:select.option>{{ $i }}</flux:select.option>
                                                            @endfor
                                                        </flux:select>
                                                    @else
                                                        <flux:description>Each {{ Str::title($tallyUnit) }} Won</flux:description>
                                                        <flux:heading class="mt-2">{{ $session->pts_per_set }}</flux:heading>
                                                    @endif
                                                </flux:field>
                                            </div>
                                            <div>
                                                <flux:field>
                                                    @if (is_null($session->processed_at))
                                                        <flux:label>Turning Up</flux:label>
                                                        <flux:select
                                                            wire:model.live="form.pts_play"
                                                            variant="listbox"
                                                            :disabled="! is_null($session->processed_at)"
                                                        >
                                                            @for ($i = 0; $i <= 1; $i++)
                                                                <flux:select.option>{{ $i }}</flux:select.option>
                                                            @endfor
                                                        </flux:select>
                                                    @else
                                                        <flux:description>Turning Up</flux:description>
                                                        <flux:heading class="mt-2">{{ $session->pts_play }}</flux:heading>
                                                    @endif
                                                </flux:field>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                            </flux:accordion.content>
                        </flux:accordion.item>
                        <flux:accordion.item>
                            <flux:accordion.heading class="mt-1.5">
                                <flux:heading size="lg">Table Player Order from High to Low Priority</flux:heading>
                            </flux:accordion.heading>

                            <flux:accordion.content class="space-y-4 mt-3">
                                <ol class="text-zinc-500 list-decimal list-inside space-y-1">
                                    <li>Most 'Points' (Pts).</li>
                                    @if ($session->isNewTableOrder())
                                        <li>Highest '{{ Str::title($tallyUnit) }} Difference' (+/-).</li>
                                        <li>Most '{{ Str::title($tallyUnit) }}s For' (F).</li>
                                        <li>Most '{{ Str::title($tallyUnit) }}s Drawn' (D).</li>
                                        <li>Most 'Played' (P).</li>
                                    @else
                                        <li>Most 'Played' (P).</li>
                                        <li>Highest '{{ Str::title($tallyUnit) }} Difference' (+/-).</li>
                                        <li>Most '{{ Str::title($tallyUnit) }}s For' (F).</li>
                                        <li>Most '{{ Str::title($tallyUnit) }}s Drawn' (D).</li>
                                    @endif
                                    <li>Initial Starting Position.</li>
                                </ol>
                                <flux:text>Results involving a withdrawn player (WD) will be ignored.</flux:text>
                                <flux:text>Player's that don't turn up for a match will not have the privilege of having that match reflected in their 'Played' (P) tally.</flux:text>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    </flux:accordion>
                </flux:card>
            </div>
            <div class="md:w-72">
                <flux:card class="">
                    <div class="flex items-center gap-2 mb-2.5">
                        <flux:heading size="lg">Status</flux:heading>
                        <x-tags.session-status-tag :$session />
                    </div>

                    <div class="space-y-4">
                        @if (! is_null($session->published_at) || ! is_null($session->processed_at))
                            <flux:table>
                                <flux:table.rows>
                                    @if (! is_null($session->published_at))
                                        <flux:table.row>
                                            <flux:table.cell class="!pl-0 !w-0">Live</flux:table.cell>
                                            <flux:table.cell class="!pl-0 !w-0 !font-medium !text-zinc-800">{{ $this->datetimeForHumans($session->published_at->timezone($session->timezone)) }}</flux:table.cell>
                                        </flux:table.row>
                                    @endif

                                    @if (! is_null($session->processed_at))
                                        <flux:table.row>
                                            <flux:table.cell class="!pl-0 !w-0">Processed</flux:table.cell>
                                            <flux:table.cell class="!pl-0 !w-0 !font-medium !text-zinc-800">{{ $this->datetimeForHumans($session->processed_at->timezone($session->timezone)) }}</flux:table.cell>
                                        </flux:table.row>
                                    @endif
                                </flux:table.rows>
                            </flux:table>
                        @endif

                        @if (is_null($session->published_at))
                            <flux:modal.trigger name="publish-session">
                                <flux:button
                                    variant="primary"
                                    class="w-full mt-3"
                                >
                                    Go Live
                                </flux:button>
                            </flux:modal.trigger>

                            @teleport('body')
                                <flux:modal name="publish-session" class="modal">
                                    <form wire:submit="publish" class="space-y-6">
                                        <x-modals.content>
                                            <x-slot:heading>{{ __('Make Session Live') }}</x-slot:heading>
                                                <flux:table class="border sm:min-w-3xs mb-2">
                                                    <flux:table.columns class="!bg-stone-50">
                                                        <flux:table.column>Table Points Awarded For...</flux:table.column>
                                                        <flux:table.column></flux:table.column>
                                                    </flux:table.columns>

                                                    <flux:table.rows>
                                                        <flux:table.row>
                                                            <flux:table.cell class="!text-zinc-900">Winning</flux:table.cell>
                                                            <flux:table.cell align="center" class="!text-zinc-900">{{ $session->pts_win }}</flux:table.cell>
                                                        </flux:table.row>
                                                        <flux:table.row>
                                                            <flux:table.cell class="!text-zinc-900">Drawing</flux:table.cell>
                                                            <flux:table.cell align="center" class="!text-zinc-900">{{ $session->pts_draw }}</flux:table.cell>
                                                        </flux:table.row>
                                                        <flux:table.row>
                                                            <flux:table.cell class="!text-zinc-900">Each {{ $tallyUnit }} Won</flux:table.cell>
                                                            <flux:table.cell align="center" class="!text-zinc-900">{{ $session->pts_per_set }}</flux:table.cell>
                                                        </flux:table.row>
                                                        <flux:table.row>
                                                            <flux:table.cell class="!text-zinc-900">Turning Up</flux:table.cell>
                                                            <flux:table.cell align="center" class="!text-zinc-900">{{ $session->pts_play }}</flux:table.cell>
                                                        </flux:table.row>
                                                    </flux:table.rows>
                                                </flux:table>
                                            <flux:text>Are you sure you wish to make this session live?</flux:text>
                                            <x-slot:buttons>
                                                <flux:button type="submit" variant="primary">Go Live</flux:button>
                                            </x-slot:buttons>
                                        </x-modals.content>
                                    </form>
                                </flux:modal>
                            @endteleport
                        @endif

                        @if (! is_null($session->published_at) && now() < $session->ends_at && is_null($session->processed_at))
                            <flux:modal.trigger name="unpublish-session">
                                <flux:button
                                    variant="filled"
                                    class="w-full"
                                >
                                    Unpublish
                                </flux:button>
                            </flux:modal.trigger>
                        @endif

                        @if (! is_null($session->processed_at) && $isLatestSession)
                            <flux:modal.trigger name="unprocess-session">
                                <flux:button
                                    variant="danger"
                                    class="w-full"
                                >
                                    Unprocess
                                </flux:button>
                            </flux:modal.trigger>

                            @teleport('body')
                                <flux:modal name="unprocess-session" class="modal">
                                    <form wire:submit="unprocess" class="space-y-6">
                                        <x-modals.content>
                                            <x-slot:heading>{{ __('Unprocess Session') }}</x-slot:heading>
                                            <flux:text>Are you sure you wish to unprocess this session?</flux:text>

                                            <x-slot:buttons>
                                                <flux:button type="submit" variant="danger">Unprocess</flux:button>
                                            </x-slot:buttons>
                                        </x-modals.content>
                                    </form>
                                </flux:modal>
                            @endteleport
                        @endif

                        @if (is_null($session->processed_at) && !is_null($session->published_at) && now() > $session->ends_at)
                            <div class="space-y-3">
                                <flux:modal.trigger name="process-session">
                                    <flux:button
                                        variant="primary"
                                        class="w-full"
                                    >
                                        Process
                                    </flux:button>
                                </flux:modal.trigger>

                                @teleport('body')
                                    <flux:modal name="process-session" class="modal">
                                        <form wire:submit="process" class="space-y-6">
                                            <x-modals.content>
                                                <x-slot:heading>{{ __('Process Session') }}</x-slot:heading>
                                                <flux:text>Are you sure you wish to process this session?</flux:text>

                                                <x-slot:buttons>
                                                    <flux:button type="submit" variant="primary">Process</flux:button>
                                                </x-slot:buttons>
                                            </x-modals.content>
                                        </form>
                                    </flux:modal>
                                @endteleport

                                <flux:modal.trigger name="unpublish-session">
                                    <flux:button
                                        variant="filled"
                                        class="mt-3 w-full"
                                    >
                                        Unpublish
                                    </flux:button>
                                </flux:modal.trigger>
                            </div>
                        @endif
                    </div>

                    @teleport('body')
                        <flux:modal name="unpublish-session" class="modal">
                            <form wire:submit="unpublish" class="space-y-6">
                                <x-modals.content>
                                    <x-slot:heading>{{ __('Unpublish Session') }}</x-slot:heading>
                                    <flux:text>Are you sure you wish to make this session not live?</flux:text>

                                    <x-slot:buttons>
                                        <flux:button type="submit" variant="primary">Unpublish</flux:button>
                                    </x-slot:buttons>
                                </x-modals.content>
                            </form>
                        </flux:modal>
                    @endteleport

                </flux:card>
            </div>
        </div>
        @foreach ($tiers as $tier)
            <div class="relative" wire:key="{{ $tier['id'] }}">
                <div class="space-y-9 border-t pt-9">

                    <!-- Divisions -->
                    @foreach ($tier['divisions'] as $division)
                        <div class="relative space-y-9 pt-0" wire:key="{{ $division['id'] }}">

                            <div class="flex items-center justify-between">
                                <flux:heading size="lg">{{ $division['name'] }}</flux:heading>



                                <div class="flex flex-col items-center">
                                    <div class="flex items-center font-medium bg-zinc-800/5 dark:bg-white/10 h-10 p-1 rounded-lg">
                                        <button
                                            href="{{ route('club.admin.leagues.sessions.tables.division.table', ['club' => $club, 'league' => $league, 'session' => $session, 'tier' => $tier['id'], 'division' => $division['id']]) }}"
                                            class="bg-white hover:bg-white text-normal text-zinc-600 shadow-xs px-7 py-1.5 rounded-md cursor-pointer"
                                            wire:navigate
                                        >
                                            Table
                                        </button>
                                        <button
                                            href="{{ route('club.admin.leagues.sessions.tables.division.matrix', ['club' => $club, 'league' => $league, 'session' => $session, 'tier' => $tier['id'], 'division' => $division['id']]) }}"
                                            class="text-zinc-500 hover:text-zinc-600 px-7 py-1.5 rounded-md cursor-pointer"
                                            wire:navigate
                                        >
                                            Results
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <x-ui.cards.table>
                                <flux:table.columns class="bg-stone-50">
                                    <flux:table.column class="w-0"></flux:table.column>
                                    <flux:table.column></flux:table.column>
                                    <flux:table.column class="w-12" align="center">P</flux:table.column>
                                    <flux:table.column class="w-12" align="center">W</flux:table.column>
                                    <flux:table.column class="w-12" align="center">D</flux:table.column>
                                    <flux:table.column class="w-12" align="center">L</flux:table.column>
                                    <flux:table.column class="w-12" align="center">F</flux:table.column>
                                    <flux:table.column class="w-12" align="center">A</flux:table.column>
                                    <flux:table.column class="w-12" align="center">+/-</flux:table.column>
                                    <flux:table.column class="w-12" align="center">Pts</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows class="!divide-y-0">

                                    <!-- Contestants -->

                                    @php
                                        $standings = $division['standings'];
                                    @endphp

                                    @foreach ($standings as $i => $contestant)
                                        @php
                                            $curr = $contestant['rank'];
                                            $prev = $standings[$i-1]['rank'] ?? null;
                                            $next = $standings[$i+1]['rank'] ?? null;
                                            $isTie = ($prev === $curr) || ($next === $curr);
                                        @endphp
                                        @if ($loop->index === $loop->count - $division['relegate_count'] && $loop->index !== $loop->count)
                                            <flux:table.row>
                                                <flux:table.cell colspan="10" class="!py-0 h-0.5 bg-red-500"></flux:table.cell>
                                            </flux:table.row>
                                        @endif
                                        <flux:table.row>
                                            <flux:table.cell>{{ $curr }}@if($isTie) =@endif</flux:table.cell>
                                            <flux:table.cell>
                                                <x-generic.table-contestant :$club :$contestant />
                                            </flux:table.cell>
                                            <flux:table.cell align="center">{{ $contestant['played'] }}</flux:table.cell>
                                            <flux:table.cell align="center">{{ $contestant['won'] }}</flux:table.cell>
                                            <flux:table.cell align="center">{{ $contestant['drawn'] }}</flux:table.cell>
                                            <flux:table.cell align="center">{{ $contestant['lost'] }}</flux:table.cell>
                                            <flux:table.cell align="center">{{ $contestant['for'] }}</flux:table.cell>
                                            <flux:table.cell align="center">{{ $contestant['against'] }}</flux:table.cell>
                                            <flux:table.cell align="center">{{ $contestant['diff'] }}</flux:table.cell>
                                            <flux:table.cell align="center" class="font-bold">{{ $contestant['points'] }}</flux:table.cell>
                                        </flux:table.row>
                                        @if ($loop->iteration !== $loop->count - $division['relegate_count'] && $loop->iteration !== $loop->count && $loop->iteration !== $division['promote_count'])
                                            <!-- <flux:table.row>
                                                <flux:table.cell colspan="10" class="!py-0 border-t"></flux:table.cell>
                                            </flux:table.row> -->
                                        @endif
                                        @if ($loop->iteration === $division['promote_count'])
                                            <flux:table.row>
                                                <flux:table.cell colspan="10" class="!py-0 h-0.5 bg-green-500"></flux:table.cell>
                                            </flux:table.row>
                                        @endif
                                    @endforeach
                                </flux:table.rows>
                            </x-ui.cards.table>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div wire:loading class="absolute inset-0 z-50 bg-white -my-3.5 opacity-50"></div>
    </div>
</div>

@script
<script>
    $js('openModal', (tierId, tierName) => {
        document.querySelectorAll('[x-ref="error"]').forEach(el => {
            el.classList.add('hidden');
        });
        document.querySelectorAll('[x-ref="error-input"]').forEach(el => {
            el.classList.remove('border-red-500');
        });

        $wire.tierNames[tierId] = tierName
    })
</script>
@endscript
