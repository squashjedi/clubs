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

        $this->form->setMember($this->member);
    }

    public function save()
    {
        $this->form->update();

        Flux::toast(
            variant: "success",
            text: "Member #{$this->member->club_member_id} updated."
        );
    }
}; ?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.members', [$club])" wire:navigate>Members</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $member->club_member_id }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-ui.typography.h3>Edit Member #{{ $member->club_member_id }}</x-ui.typography.h3>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <flux:input wire:model="form.first_name" label="First Name" class="max-w-sm" />
            <flux:input wire:model="form.last_name" label="Last Name" class="max-w-sm" />
        </div>
        <flux:field>
            <flux:label>User</flux:label>
            <!-- <flux:card class="space-y-6"> -->
                <flux:description><span class="font-semibold">Please note:</span> User has the privilege of being able to submit results.</flux:description>
                <livewire:__components.buttons.invite-member :$club :$member />
            <!-- </flux:card> -->
        </flux:field>
        <div class="flex">
            <flux:spacer />
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
