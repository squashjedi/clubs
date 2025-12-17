<?php

use Flux\Flux;
use App\Models\Tier;
use App\Models\Session;
use Livewire\Volt\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    public Session $session;

    public Tier $tier;

    public string $tierName;

    public function mount()
    {
        $this->tierName = $this->tier->name;
    }

    public function save()
    {
        $this->validate([
            "tierName" => 'required|string|max:20',
        ], [
            "tierName.max" => 'Must not be more than 20 characters.'
        ]);

        $this->tier->update([
            'name' => $this->tierName,
        ]);

        $this->dispatch('tier-name-updated');

        Flux::modals()->close('tier-name');

        Flux::toast(
            variant: 'success',
            text: 'Tier' . ($this->tier->index + 1) . ' Name updated.'
        );
    }
};
?>

<div>
    @if (is_null($session->processed_at))
        <flux:button
            x-on:click="$js.openModal('{{ $tier->name }}');$flux.modal('tier-name').show()"
            variant="subtle"
            size="xs"
            icon="pencil-square"
            icon:variant="outline"
        />
    @endif

    @teleport('body')
        <flux:modal name="tier-name" class="modal">
            <form wire:submit="save">
                <x-modals.content>
                    <x-slot:heading>{{ __('Edit Tier Name') }}</x-slot:heading>
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>{{ __('Tier') }} {{ $tier['index'] + 1 }} Name</flux:label>
                            <flux:input
                                x-ref="error-input"
                                wire:model="tierName"
                                placeholder="e.g. Premier"
                                required
                                autofocus
                            />
                            <flux:error x-ref="error" name="tierName" />
                        </flux:field>
                    </div>
                    <x-slot:buttons>
                        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                    </x-slot:buttons>
                </x-modals.content>
            </form>
        </flux:modal>
    @endteleport
</div>

@script
<script>
    $js('openModal', (tierName) => {
        document.querySelector('[x-ref="error"]').classList.add('hidden');
        document.querySelector('[x-ref="error-input"]').classList.remove('border-red-500');
        $wire.tierName = tierName;
    })
</script>
@endscript
