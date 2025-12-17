<?php

use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Component;
use App\Models\Division;
use Livewire\Attributes\On;

new class extends Component
{
    public Club $club;

    public Session $session;

    public League $league;

    public Division $division;

    #[On([
        'division-movement-updated',
        'competitor-added',
        'competitor-removed',
        'tier-name-updated'
    ])]
    public function refreshComponent()
    { }
};
?>

<div>
    @if ($division->session->tiers()->count() > 1)
        @php
            $page = request()->routeIs('club.admin.leagues.sessions.tables.division.matrix') ? 'matrix' : 'table';
        @endphp
        <flux:dropdown>
            <flux:button
                icon-trailing="chevron-down"
            >
                {{ __('Hierarchy') }}
            </flux:button>

            <flux:navmenu>
                @foreach ($division->session->tiers as $tierIndex => $tierModal)
                    @foreach ($tierModal->divisions as $tierDivisionIndex => $tierDivision)
                        @php
                            $sameDivision = $tierDivision->id === $division->id;
                        @endphp
                        <flux:navmenu.item
                            @class([
                                'flex items-center justify-between gap-4'
                            ])
                            href="{{ route('club.admin.leagues.sessions.tables.division.'.$page, [$club, $league, $session, 'tier' => $tierModal, 'division' => $tierDivision]) }}"
                            wire:navigate
                        >
                            <div class="flex items-center">
                                @if ($sameDivision)
                                @endif
                                <span>{{ $tierDivision->name() }}</span><span class="text-zinc-500 ml-1 text-xs">({{ $tierDivision->contestant_count }})</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($tierIndex > 0)
                                    <div class="flex items-center w-5">
                                        <flux:icon.arrow-up variant="micro" class="size-3 text-green-600" />
                                        <flux:text class="!text-xs !text-green-600">{{ $tierDivision->promote_count }}</flux:text>
                                    </div>
                                @endif
                                @if (! $loop->parent->last)
                                    <div class="flex items-center w-5">
                                        <flux:icon.arrow-down variant="micro" class="size-3 text-red-600" />
                                        <flux:text class="!text-xs !text-red-600">{{ $tierDivision->relegate_count }}</flux:text>
                                    </div>
                                @endif
                            </div>
                        </flux:navmenu.item>
                    @endforeach
                    @if (! $loop->last)
                        <flux:navmenu.separator />
                    @endif
                @endforeach
            </flux:navmenu>
        </flux:dropdown>
    @endif
</div>
