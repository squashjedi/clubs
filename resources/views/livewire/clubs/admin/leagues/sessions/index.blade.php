<?php

use App\Models\Club;
use App\Models\League;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.club.admin.app')] class extends Component {
    public Club $club;
    public League $league;

    public function mount()
    {
        $this->authorize('view', $this->club);
    }

    public function with(): array
    {
        $query = $this->league->sessions();
        // $query = $this->applySearch($query);
        // $query = $this->applySorting($query);

        return [
            'session_count' => $this->league->sessions()->count(),
            'sessions' => $query->orderBy('id', 'desc')->paginate(20),
        ];
    }
}; ?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues', [$club])" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues.edit', [$club, $league])" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Sessions") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <x-clubs.admin.league-menu :$club :$league />
    <x-ui.typography.h4>{{ __("Sessions") }}</x-ui.typography.h4>

    @if ($session_count === 0)
        <div>There are no league sessions.</div>
    @else
        <!-- <flux:input wire:model.live.debounce.500ms="search" icon="magnifying-glass" placeholder="Search # or league" class="max-w-md" /> -->

        @if ($sessions->total() > 0)
            <div class="relative">
                <flux:table :paginate="$sessions">
                    <flux:table.columns>
                        <flux:table.column>Active Period</flux:table.column>
                        <flux:table.column align="end"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($sessions as $session)
                            <flux:table.row>
                                <flux:table.cell>{{ $session->active_period }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button :href="route('clubs.admin.leagues.sessions.show', [$club, $league, $session])" icon="pencil" size="xs" wire:navigate></flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div wire:loading class="absolute inset-0 bg-white opacity-50"></div>
                <div wire:loading.flex class="flex items-center justify-center absolute inset-0">
                    <flux:icon.loading class="size-10 text-gray-500" />
                </div>
            </div>
        @else
            <div>Sorry, no results found for '{{ $search }}'.</div>
        @endif
    @endif
</div>
