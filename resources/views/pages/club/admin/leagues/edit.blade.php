<?php

use App\Models\Club;
use App\Models\Sport;
use App\Models\League;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

    public League $league;

    public function mount(League $league)
    {
        $this->league = League::withTrashed()->findOrFail($league->id);
    }
}; ?>

<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [ $club ]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [ $club ]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $league->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <livewire:formz.league-form :$club :$league :isEdit="true" />
</div>