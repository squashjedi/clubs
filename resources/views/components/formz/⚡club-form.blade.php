<?php

use Flux\Flux;
use App\Models\Club;
use Livewire\Component;
use App\Livewire\Forms\ClubForm;

new class extends Component
{
    public ?Club $club;

    public ClubForm $form;

    public $is_edit = false;

    public function mount()
    {
        if ($this->is_edit) {
            $this->form->setClub($this->club);
        }
    }

    public function save()
    {
        if (! $this->is_edit) {
            $club = $this->form->store();

            Flux::toast(
                variant: 'success',
                text: $club->name. ' created.'
            );

            $this->redirectRoute('club.admin', [ $club ], navigate: true);

            return;
        }

        $this->form->update();

        Flux::toast(
            variant: 'success',
            text: 'Club profile updated.'
        );

        $club = $this->club->fresh();

        if ($club->slug !== $this->club->slug) {
            $this->redirectRoute('club.admin.profile', ['club' => $club], navigate: true);
        }

    }
}; ?>

<x-ui.cards.mobile>
    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="form.name" label="Club Name" class="max-w-sm" />

        <flux:field class="max-w-sm">
            <flux:label>Timezone</flux:label>
            <flux:select wire:model="form.timezone" variant="listbox" searchable placeholder="Choose timezone..." clearable>
                @foreach (config('timezones') as $tz)
                    <flux:select.option value="{{ $tz['timezone'] }}">{{ $tz['name'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="form.timezone" />
        </flux:field>

        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
    </form>
</x-ui.cards.mobile>