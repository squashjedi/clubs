<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Member;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Livewire\Forms\MemberForm;

new #[Layout('components.layouts.club.admin.app')] class extends Component {
    public Member $member;

    public Club $club;

    public MemberForm $form;

    public function mount()
    {
        $this->authorize('view', $this->club);
    }

    public function save()
    {
        $member = $this->form->store($this->club);

        Flux::toast(
            variant: "success",
            text: "Member #{$member->club_member_id} created."
        );

        $this->redirectRoute('clubs.admin.members', ['club' => $this->club], navigate: true);
    }
}; ?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.members', [$club])" wire:navigate>Members</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-ui.typography.h3>Add New Member</x-ui.typography.h3>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <flux:input wire:model="form.first_name" label="First Name" />
            <flux:input wire:model="form.last_name" label="Last Name" />
        </div>
        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</div>
