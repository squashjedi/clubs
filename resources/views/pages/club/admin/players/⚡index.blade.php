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
        <flux:breadcrumbs.item>Members</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-headings.page-heading>
        <div class="flex-1 flex items-center justify-between">
            <div>Members</div>
            <flux:button
                href="{{ route('club.admin.players.create', [$club]) }}"
                variant="primary"
                icon="plus"
                wire:navigate
            >
                Member
            </flux:button>
        </div>
    </x-headings.page-heading>

    <livewire:tables.club-admin-players-table :$club />
</div>