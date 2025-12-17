<?php

use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    #[On('structure')]
    public function reload()
    {
    }
}; ?>

<flux:navbar class="border-b">

    <flux:navbar.item href="{{ route('club.admin.leagues.sessions.competitors', [ $club, $league, $session ]) }}" wire:navigate>{{ __('Competitors') }}</flux:navbar.item>

    <flux:navbar.item href="{{ route('club.admin.leagues.sessions.structure', [ $club, $league, $session ]) }}" class="relative" wire:navigate>
        @if ($session->isStructureDifferentFromSeedings())
            <div class="absolute inset-x-0 -top-5 flex justify-center">
                <flux:icon.exclamation-triangle variant="solid" class="animate-bounce size-6 text-yellow-500 dark:text-yellow-300" />
            </div>
        @endif
        <div class="flex items-center gap-3">
            <div>{{ __('Structure') }}</div>
        </div>
    </flux:navbar.item>

    <flux:navbar.item href="{{ route('club.admin.leagues.sessions.rules', [$club, $league, $session]) }}" wire:navigate>{{ __('Rules') }}</flux:navbar.item>

    <flux:navbar.item href="{{ route('club.admin.leagues.sessions.tables', [$club, $league, $session]) }}" :current="request()->routeIs('club.admin.leagues.sessions.tables') || request()->routeIs('club.admin.leagues.sessions.tables.division')" wire:navigate>
        <div class="flex items-center gap-3">
            <div>{{ __('Tables') }}</div>
        </div>
    </flux:navbar.item>

</flux:navbar>