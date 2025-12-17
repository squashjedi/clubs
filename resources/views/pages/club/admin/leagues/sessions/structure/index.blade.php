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

    public function mount()
    {
        if ($this->session->isBuilt()) {
            $this->redirectRoute('club.admin.leagues.sessions.tables', [ 'club' => $this->club, 'league' => $this->league, 'session' => $this->session ], navigate: true);
        }
    }
}; ?>

<div class="space-y-main relative">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [$club]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.edit', [$club, $league]) }}" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions', [$club, $league]) }}" wire:navigate>Sessions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions.show', [$club, $league, $session]) }}" wire:navigate>{{ $session->active_period }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item >{{ __('Structure') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-containers.club-admin-session-builder :$club :$league :$session>
        <livewire:generic.session-structure lazy :$club :$league :$session />
    </x-containers.club-admin-session>
</div>
