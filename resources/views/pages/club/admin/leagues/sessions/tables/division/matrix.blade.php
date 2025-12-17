<?php

use App\Models\Club;
use App\Models\Tier;
use App\Models\League;
use App\Models\Session;
use Livewire\Component;
use App\Models\Division;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;
    public League $league;
    public Session $session;
    public Tier $tier;
    public Division $division;

    public function with(): array
    {
        return [
            'divisionName' => $this->division->name(),
            'tiers' => $this->session->tiers()->with(['divisions' => fn ($q) => $q->orderBy('index')])->get(),
        ];
    }
}; ?>

<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [$club]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.edit', [$club, $league]) }}" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions', [$club, $league]) }}" wire:navigate>Sessions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions.show', [$club, $league, $session]) }}" wire:navigate>{{ $session->active_period }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions.tables', [$club, $league, $session]) }}" wire:navigate>{{ __('Tables') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions.tables.division.table', [$club, $league, $session, $tier, $division]) }}" wire:navigate>{{ $divisionName }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Results</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-containers.club-admin-session-division :$club :$league :$session :$tier :$division>
        <livewire:generic.matrix lazy :$club :$league :$session :$division />
    </x-containers.club-admin-session-division>

</div>
