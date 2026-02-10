<?php

use App\Models\Club;
use App\Models\Player;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

    public Player $player;

    public function mount(Player $player)
    {
        $this->player = $this->club->players()->findOrFail($player->id);
    }
}; ?>

<div class="space-y-main" x-data x-init="const scrollTop = () => window.scrollTo(0, 0); scrollTop(); document.addEventListener('livewire:navigated', scrollTop, { once: true })">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.players', [$club]) }}" wire:navigate>Members</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $player->pivot->club_player_id }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <livewire:formz.club-player-form :$club :$player :isEdit="true" />
</div>
