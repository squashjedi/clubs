<?php

use App\Models\Club;
use App\Models\Member;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

    public Member $member;

    public function mount(Member $member)
    {
        $this->member = Member::withTrashed()->findOrFail($member->id);
    }
}; ?>

<div class="space-y-main" x-data x-init="const scrollTop = () => window.scrollTo(0, 0); scrollTop(); document.addEventListener('livewire:navigated', scrollTop, { once: true })">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.members', [$club]) }}" wire:navigate>Members</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $member->club_member_id }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <livewire:formz.member-form :$club :$member :isEdit="true" />
</div>
