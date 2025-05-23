<?php

use App\Models\Club;
use Livewire\Volt\Component;
use App\Rules\ForbiddenSlugs;
use Livewire\Attributes\Layout;
use App\Livewire\Forms\ClubForm;

new #[Layout('components.layouts.club.admin.app')] class extends Component
{
    public ClubForm $form;

    public Club $club;

    public function mount(Club $club)
    {
        $this->authorize('view', $this->club);

        $this->form->setClub($club);
    }
}; ?>


<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Profile</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex justify-between">
        <x-ui.typography.h3>{{ __("Profile") }}</x-ui.typography.h3>
    </div>

    <livewire:__components.forms.club-form :$form :is_edit="true" />
</div>