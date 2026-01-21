<?php

use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public array $tierNames = [];

    public function mount()
    {
        if (! $this->session->isBuilt()) {
            $this->redirectRoute('club.admin.leagues.sessions.entrants', ['club' => $this->club, 'league' => $this->league, 'session' => $this->session], navigate: true);
        }
    }
}; ?>


<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>{{ __("Dashboard") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [$club]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.edit', [$club, $league]) }}" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions', [$club, $league]) }}" wire:navigate>{{ __("Sessions") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions.show', [$club, $league, $session]) }}" wire:navigate>{{ $session->active_period }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Tables") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-containers.club-admin-session :$club :$league :$session :showNavbar="true">
        <livewire:generic.tables :$club :$league :$session />
    </x-containers.club-admin-session>
</div>
