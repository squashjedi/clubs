<?php

use Flux\Flux;
use App\Models\Club;
use App\Enums\Status;
use Livewire\Volt\Component;

new class extends Component
{
    public Club $club;
    public bool $isFollowing = false;

    public function mount(Club $club)
    {
        $this->club = $club;

        $this->isFollowing = $this->club->users()->wherePivot('user_id', auth()->id())->first() ? true : false;
    }

    public function follow()
    {
        if (! $this->isFollowing) {

            $this->club->users()->attach(auth()->id());
            $this->isFollowing = true;

            Flux::toast(
                variant: 'success',
                text: 'You are now following this club.'
            );

        } else {

            $this->club->users()->detach(auth()->id());
            $this->isFollowing = false;

            Flux::toast(
                variant: 'success',
                text: 'You are no longer following this club.'
            );

        }

        Flux::modals()->close();
    }
}; ?>


<div>
    <flux:modal.trigger name="follow">
        <flux:badge as="button" variant="pill" size="lg">{{ $isFollowing ? 'Unfollow' : 'Follow' }}</flux:badge>
    </flux:modal.trigger>

    <flux:modal name="follow" class="md:w-96">
        <div class="space-y-6">
            @if (! $isFollowing)
                <div class="space-y-3">
                    <flux:heading size="lg">Follow Club</flux:heading>
                    <flux:text>Are you sure you wish to follow this club?</flux:text>
                </div>

                <div class="flex">
                    <flux:spacer />
                    <form wire:submit="follow">
                        <flux:button type="submit" variant="primary" class="cursor-pointer">Confirm</flux:button>
                    </form>
                </div>
            @else
                <div class="space-y-3">
                    <flux:heading size="lg">Unfollow Club</flux:heading>
                    <flux:text>Are you sure you wish to unfollow this club?</flux:text>
                </div>

                <div class="flex">
                    <flux:spacer />
                    <form wire:submit="follow">
                        <flux:button type="submit" variant="danger" class="cursor-pointer">Confirm</flux:button>
                    </form>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
