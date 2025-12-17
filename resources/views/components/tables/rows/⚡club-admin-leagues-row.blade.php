<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\League;
use Livewire\Component;

new class extends Component {
    public Club $club;

    public League $league;

    public bool $isAssigned = false;

    public bool $isArchived = false;

    public bool $show = true;

    public function mount()
    {
        $this->isArchived = $this->league->trashed();
    }

    public function restore()
    {
        $this->dispatch('league-restored');

        $this->league->restore();

        Flux::toast(
            variant: 'success',
            text: "{$this->league->name} restored."
        );
    }

    public function delete()
    {
        $this->dispatch('league-deleted');

        $this->league->permanentlyDelete();

        Flux::toast(
            variant: 'success',
            text: "{$this->league->name} deleted."
        );
    }

    public function archive()
    {
        $this->dispatch('league-archived');

        $this->league->delete();

        Flux::toast(
            variant: 'success',
            text: "{$this->league->name} archived."
        );
    }
}; ?>

<flux:table.row
    x-data="{ show: $wire.show }"
    x-show="show"
    class="{{ $league->trashed() ? 'bg-archived' : '' }}"
>
    <flux:table.cell>{{ $league->club_league_id }}</flux:table.cell>
    <flux:table.cell>
        <flux:badge>{{ $league->sport->name }}</flux:badge>
    </flux:table.cell>
    <flux:table.cell>
        <div class="flex items-center gap-1">
            {{ $league->name }}
            @if ($league->trashed())
                <flux:icon.no-symbol
                    variant="micro"
                    class="text-red-500 inline-block"
                />
            @endif
        </div>
    </flux:table.cell>
    <flux:table.cell align="end">
        @if ($league->trashed())
            <flux:button href="{{ route('club.admin.leagues.edit', [$club, $league]) }}" icon="pencil-square" icon:variant="outline" size="sm" variant="subtle" wire:navigate />
        @else
            @if ($league->latestSession)
                <flux:button href="{{ route('club.admin.leagues.sessions.entrants', [$club, $league, 'session' => $league->latestSession]) }}" icon="pencil-square" icon:variant="outline" size="sm" variant="subtle" wire:navigate />
            @else
                <flux:button href="{{ route('club.admin.leagues.sessions.create', [$club, $league]) }}" icon="pencil-square" icon:variant="outline" size="sm" variant="subtle" wire:navigate />
            @endif
        @endif
    </flux:table.cell>
</flux:table.row>