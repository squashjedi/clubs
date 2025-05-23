<?php

use Flux\Flux;
use App\Models\Club;
use Livewire\Volt\Component;
use App\Rules\ForbiddenSlugs;
use Livewire\Attributes\Layout;
use App\Livewire\Forms\ClubForm;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.user')] class extends Component
{
    public ClubForm $form;

    public function save()
    {
        $club = $this->form->store();

        $this->redirectRoute('clubs.admin', [ $club ], navigate: true);
    }
}; ?>

<section class="w-full">
    @include('partials.clubs-heading')

    <x-clubs.layout :heading="__('Create a Club')" :subheading="__('Complete the form to create a new club and become it\'s webmaster.')">
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="form.name" label="Club name" />

            <flux:field>
                <flux:label>Timezone</flux:label>
                <flux:select wire:model="form.timezone" variant="listbox" searchable placeholder="Choose timezone..." clearable>
                    @foreach (config('timezones') as $tz)
                        <flux:select.option value="{{ $tz['timezone'] }}">{{ $tz['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.timezone" />
            </flux:field>

            <flux:button type="submit" variant="primary">Save</flux:button>
        </form>
    </x-clubs.layout>
</section>
