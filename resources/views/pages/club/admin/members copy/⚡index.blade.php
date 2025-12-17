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
        <flux:breadcrumbs.item>{{ __("Members") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-headings.page-heading>
        <div class="flex-1 flex items-center justify-between">
            <flux:heading variant="strong" size="xl">{{ __("Members") }}</flux:heading>
            <flux:button href="{{ route('club.admin.members.create', [$club]) }}" variant="primary" icon="plus" wire:navigate>Member</flux:button>
        </div>
    </x-headings.page-heading>

    <livewire:tables.club-admin-members-table lazy :$club />
</div>