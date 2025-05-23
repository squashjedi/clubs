<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Sport;
use Livewire\Volt\Component;
use App\Livewire\Forms\ClubForm;

new class extends Component {
    public ClubForm $form;

    public $is_edit = false;

    public function save()
    {
        if (! $this->is_edit) {
            $club = $this->form->store();

            Flux::toast(
                variant: 'success',
                text: $club->name. ' created.'
            );

            $this->redirectRoute('clubs.admin', [ $club ], navigate: true);

            return;
        }

        $this->form->update();

        Flux::toast(
            variant: 'success',
            text: 'Club profile updated.'
        );

        $this->redirectRoute('clubs.admin.profile', ['club' => $this->form->club], navigate: true);
    }

    public function with(): array
    {
        return [
            'sports' => Sport::orderBy('name')->get(),
        ];
    }
}; ?>

<form wire:submit="save" class="space-y-6 max-w-lg">
    <flux:input wire:model="form.name" label="Club name" />

    <flux:checkbox.group wire:model="form.sports">
        <flux:field>
            <flux:label>Sports</flux:label>
            <flux:description>Choose the sports your club supports.</flux:description>
            <div class="flex gap-4 *:gap-x-2 flex-wrap">
                @foreach ($sports as $sport)
                    <flux:checkbox label="{{ $sport->name }}" value="{{ $sport->id }}" />
                @endforeach
            </div>
            <flux:error name="form.sports" />
        </flux:field>
    </flux:checkbox.group>

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
