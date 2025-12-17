<?php

use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Carbon\CarbonInterface;
use Livewire\Volt\Component;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;
}; ?>

<flux:table.row>
    <flux:table.cell>
        {{ $session->active_period }}</div>
    </flux:table.cell>
    <flux:table.cell>
        @if (is_null($session->published_at))
            <flux:badge size="sm">Draft</flux:badge>
        @endif

        @if (! is_null($session->published_at) && now() < $session->starts_at)
            <flux:badge color="amber" size="sm">Starts in {{ $session->starts_at->timezone($session->timezone)->diffForHumans(now(), ['syntax' => CarbonInterface::DIFF_ABSOLUTE], false, 2) }}</flux:badge>
        @endif

        @if (! is_null($session->published_at) && now() > $session->starts_at && now() <= $session->ends_at)
            <flux:badge color="green" size="sm">Ends in {{ $session->ends_at->timezone($session->timezone)->diffForHumans(now(), ['syntax' => CarbonInterface::DIFF_ABSOLUTE], false, 2) }}</flux:badge>
        @endif

        @if (is_null($session->processed_at) && isset($session->published_at) && now() > $session->ends_at)
            <flux:badge color="red" size="sm">Waiting to be processed</flux:badge>
        @endif

        @if (! is_null($session->processed_at))
            <flux:badge color="blue" size="sm">Processed</flux:badge>
        @endif
    </flux:table.cell>
    <flux:table.cell align="end">
        <flux:button href="{{ route('club.admin.leagues.sessions.show', [ $club, $league, $session ]) }}" icon="pencil-square" icon:variant="outline" size="sm" variant="subtle" wire:navigate />
    </flux:table.cell>
</flux:table.row>