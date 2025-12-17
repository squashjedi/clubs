<?php

use App\Models\Club;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

}; ?>


<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Leagues") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <flux:heading variant="strong" size="xl">{{ __("Leagues") }}</flux:heading>
        <flux:button href="{{ route('club.admin.leagues.create', [ $club ]) }}" icon="plus" variant="primary" wire:navigate>{{ __("League") }}</flux:button>
    </div>

    <livewire:tables.club-admin-leagues-table lazy :$club />
</div>