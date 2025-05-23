<?php

use App\Models\Club;
use App\Models\Member;
use Livewire\Volt\Component;

new class extends Component {
    public Club $club;

    public Member $member;
}; ?>

<flux:table.row style="height:65px;">
    <flux:table.cell>{{ $member->club_member_id }}</flux:table.cell>
    <flux:table.cell>{{ $member->full_name }}</flux:table.cell>
    <flux:table.cell>
        <livewire:__components.buttons.invite-member :$club :$member>
    </flux:table.cell>
    <flux:table.cell>
        <flux:button :href="route('clubs.admin.members.edit', [$club, $member])" size="xs" icon="pencil" variant="subtle" wire:navigate />
    </flux:table.cell>
</flux:table.row>