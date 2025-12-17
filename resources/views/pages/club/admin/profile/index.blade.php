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
        <flux:breadcrumbs.item>Profile</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-headings.page-heading>
        Club Profile
    </x-headings.page-heading>
    <livewire:formz.club-form :$club :is_edit="true" />
</div>