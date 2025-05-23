<?php

use Carbon\Carbon;
use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.club.admin.app')] class extends Component {
    public Club $club;
    public League $league;
    public Session $session;
    public array $range;

    public function mount()
    {
        $this->authorize('view', $this->club);
        $this->range = [
            'start' => Carbon::parse($this->session->starting_at),
            'end' => Carbon::parse($this->session->ending_at)
        ];
    }

    public function save()
    {
        $this->session->update([
            'starting_at' => $this->range['start']->startOfDay(),
            'ending_at' => $this->range['end']->endOfDay(),
        ]);
    }
}; ?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues', [$club])" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues.edit', [$club, $league])" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues.sessions', [$club, $league])" wire:navigate>Sessions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues.sessions.show', [$club, $league, $session])" wire:navigate>{{ $session->active_period }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <x-clubs.admin.league-menu :$club :$league :$session />
    <x-ui.typography.h4>{{ $session->active_period }}</x-ui.typography.h4>
    <x-ui.typography.h4>Edit Session</x-ui.typography.h4>
    <form wire:submit="save" class="space-y-6" x-cloak>
        <flux:card>
            <flux:field class="space-y-6 max-w-md">
                <flux:label>Active Period</flux:label>
                <flux:date-picker mode="range" wire:model="range" />
            </flux:field>
        </flux:card>
        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</div>
