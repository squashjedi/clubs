<?php

use Flux\Flux;
use Carbon\Carbon;
use App\Models\Club;
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

    public Session $session;

    public Division $division;

    public int $promoteCount;

    public int $relegateCount;

    public string $tab;

    public ?array $selectedMember = [
        'id' => null
    ];

    public array $matrix = [];

    public function mount()
    {
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

        Flux::toast(
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

        Flux::toast(
            variant: 'success',
            text: 'Relegate count updated.'
        );
    }
};
?>

<div class="relative space-y-8">
    @php
        $showPromoteWarning = $division->promote_count === 0;
        $showRelegateWarning = $division->relegate_count === 0;
        $showPromote = $division->tier->index > 0;
        $showRelegate = $division->tier->index + 1 !== $division->session->tiers()->count();
    @endphp
    @if ($showPromote || $showRelegate)
        <div class="flex flex-col items-center sm:items-start">
            <div class="flex items-center justify-center gap-6 min-h-10">
                @if ($showPromote)
                    <div class="flex flex-col items-end">
                        <div class="flex items-center gap-0.5">
                            <flux:text>Promote</flux:text>
                            <flux:icon.arrow-up variant="micro" class="size-4 text-green-600 ml-0.5" />
                            @if (is_null($session->processed_at))
                                <flux:select
                                    wire:model.live="promoteCount"
                                    @class([
                                        '!border-amber-300 !bg-amber-50' => $showPromoteWarning,
                                        'cursor-not-allowed' => $session->processed_at
                                    ])
                                    :disabled="! is_null($session->processed_at)"
                                >
                                    @foreach (range(0, $division->contestant_count - $division->relegate_count) as $i)
                                        <flux:select.option>{{ $i }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @if ($division->promote_count === 0)
                                    <flux:icon.exclamation-circle variant="micro" class="text-amber-500" />
                                @endif
                            @else
                                <flux:heading class="!text-green-600">{{ $division->promote_count }}</flux:heading>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($showRelegate)
                    <div class="flex flex-col items-end">
                        <div class="flex items-center gap-0.5">
                            <flux:text>Relegate</flux:text>
                            <flux:icon.arrow-down variant="mini" class="size-4 text-red-600 ml-0.5" />
                            @if (is_null($session->processed_at))
                                <flux:select
                                    wire:model.live="relegateCount"
                                    @class([
                                        '!border-amber-300 !bg-amber-50' => $showRelegateWarning,
                                        'cursor-not-allowed' => $session->processed_at
                                    ])
                                    :disabled="! is_null($session->processed_at)"
                                >
                                    @foreach (range(0, $division->contestant_count - $division->promote_count) as $i)
                                        <flux:select.option>{{ $i }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @if ($division->relegate_count === 0)
                                    <flux:icon.exclamation-circle variant="mini" class="text-amber-500" />
                                @endif
                            @else
                                <flux:heading class="!text-red-600">{{ $division->relegate_count }}</flux:heading>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <flux:table class="border">
        <flux:table.columns class="bg-stone-50">
            <flux:table.column class="w-0"></flux:table.column>
            <flux:table.column sticky></flux:table.column>
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
            @php $standings = $division->calculateStandings()['standings']; @endphp
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
                    <flux:table.cell>{{ $curr }}@if($isTie) =@endif</flux:table.cell>
                    <flux:table.cell sticky class="flex items-center justify-between gap-4">
                        <div class="@if($contestant['trashed']) opacity-50 @endif">
                            <flux:heading>
                                <x-generic.member :$club :member="$contestant" />
                            </flux:heading>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($contestant['trashed'])
                                <flux:badge color="red" size="sm">WD</flux:badge>
                            @endif
                        </div>
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
    </flux:table>

    <div wire:loading class="absolute inset-0 z-20 bg-white -my-3.5 opacity-50"></div>

</div>