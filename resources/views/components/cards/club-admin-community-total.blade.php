@props([
    'title',
    'href',
    'collection',
])

<flux:card class="flex flex-col items-center">
    <flux:link
        href="{{ $href }}"
        variant="subtle"
        wire:navigate
    >
        {{ $title }}
    </flux:link>
    <flux:heading size="xl">{{ $collection->count() }}</flux:heading>
</flux:card>