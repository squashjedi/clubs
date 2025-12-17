<?php

use App\Enums\Gender;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Livewire\Forms\PlayerForm;

new #[Layout('layouts.user')] class extends Component
{
    public PlayerForm $form;

    public function save()
    {
        $this->form->store();

        Flux::toast(
            variant: 'success',
            text: 'Player added.'
        );

        $this->redirectRoute('settings.players', navigate: true);
    }
};
?>

<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Players')" :subheading=" __('Update the appearance settings for your account')">
        <form
            wire:submit="save"
            class="grid gap-6"
        >
            <x-formz.player-form />
        </form>
    </x-settings.layout>
</div>