<?php

use Flux\Flux;
use Carbon\Carbon;
use App\Enums\Gender;
use App\Models\Player;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Enums\PlayerRelationship;
use App\Livewire\Forms\PlayerForm;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.user')] class extends Component
{
    public Player $player;

    public PlayerForm $form;

    public function mount(Player $player)
    {
        $this->authorize('view', $this->player);

        $this->form->setPlayer($player);
    }

    public function save()
    {
        $this->form->update();

        Flux::toast(
            variant: 'success',
            text: 'Player updated.'
        );

        $this->redirectRoute('settings.players.edit', ['player' => $this->player], navigate: true);
    }

    public function deletePlayer()
    {
        $this->player->forceDelete();

        Flux::toast(
            variant: 'success',
            text: 'Player deleted.'
        );

        $this->redirectRoute('settings.players', navigate: true);
    }

    public function with(): Array
    {
        return [
            'hasMember' => (bool) $this->player->member()->exists(),
        ];
    }
}; ?>

<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Players')" :subheading=" __('Update the appearance settings for your account')">
        <form
            wire:submit="save"
            class="grid gap-6"
        >
            <flux:heading size="lg">Player ID: {{ $player->id }}</flux:heading>

            <x-formz.player-form :$hasMember />
        </form>
    </x-settings.layout>
</div>
