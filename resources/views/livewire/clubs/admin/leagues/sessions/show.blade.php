<?php

use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.club.admin.app')] class extends Component {
    public Club $club;
    public League $league;
    public Session $session;

    public function mount()
    {
        $this->authorize('view', $this->club);
    }
}; ?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues', [$club])" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues.edit', [$club, $league])" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues.sessions', [$club, $league])" wire:navigate>Sessions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $session->active_period }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <x-clubs.admin.league-menu :$club :$league :$session />
    <div class="flex items-center gap-3">
        <x-ui.typography.h4>{{ $session->active_period }}</x-ui.typography.h4>
        <flux:button variant="primary" icon="pencil-square" size="xs" href="{{ route('clubs.admin.leagues.sessions.edit', [$club, $league, $session]) }}">Session</flux:button>
    </div>
</div>
