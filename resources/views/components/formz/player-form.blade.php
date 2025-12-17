@php
    use App\Enums\Gender;
@endphp

@props([
    'player' => null,
    'hasMember' => false,
])

<div class="space-y-6">
    <div class="grid sm:grid-cols-2 gap-6">
        <flux:input wire:model="form.first_name" label="First Name" />
        <flux:input wire:model="form.last_name" label="Last Name" />
    </div>

    <flux:radio.group
        wire:model="form.gender"
        label="Gender"
        variant="cards"
        :indicator="false"
        class="max-w-sm"
    >
        @foreach (collect(Gender::cases())->reject(fn($g) => $g === Gender::Unknown) as $gender)
            <flux:radio
                label="{{ $gender->name }}"
                value="{{ $gender->value }}"
                class="text-center"
            />
        @endforeach
    </flux:radio.group>

    <flux:date-picker wire:model="form.dob" selectable-header clearable label="Date of Birth" class="max-w-xs" />

    <flux:card class="!bg-stone-50 !space-y-4">
        <div class="space-y-3">
            <flux:heading>Contact Details</flux:heading>
            <div class="space-y-0.5">
            <flux:text>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</flux:text>
            <flux:text class="flex items-center gap-2">
                <flux:icon.envelope variant="mini" class="size-4 inline-block" />
                {{ Auth::user()->email }}
            </flux:text>
            <flux:text class="flex items-center gap-2">
                <flux:icon.phone variant="mini" class="size-4 inline-block" />
                {{ Auth::user()->tel_no }}
            </flux:text>
            </div>
        </div>
    </flux:card>

    <div class="flex items-center gap-2">
        @if (! $hasMember)
            <flux:modal.trigger name="delete-player">
                <flux:button
                    variant="danger"
                >
                    Delete
                </flux:button>
            </flux:modal.trigger>

            @teleport('body')
                <flux:modal name="delete-player" class="modal">
                    <form wire:submit="deletePlayer">
                        <x-modals.content>
                            <x-slot:heading>{{ __('Delete Player') }}</x-slot:heading>
                                <flux:text>Are you sure you wish to delete this player?</flux:text>
                                <x-slot:buttons>
                                    <flux:button
                                        type="submit"
                                        variant="danger"
                                    >
                                        Delete
                                    </flux:button>
                                </x-slot:buttons>
                        </x-modals.content>
                    </form>
                </flux:modal>
            @endteleport
        @endif

        <flux:button
            type="submit"
            variant="primary"
        >
            Save
        </flux:button>
    </div>
</div>