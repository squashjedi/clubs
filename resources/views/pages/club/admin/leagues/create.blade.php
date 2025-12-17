<?php

use App\Models\Club;
use App\Models\Sport;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component {
    public Club $club;
}; ?>

<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [$club]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Create") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <livewire:formz.league-form :$club />
</div>