@props([
    'actions' => null,
])

<flux:callout variant="warning" icon="exclamation-triangle" heading="New structure created" class="sticky top-8 z-50">
    <flux:callout.text>Please review and save.</flux:callout.text>

    @if (!is_null($actions))
        <x-slot name="actions">
            {{ $actions }}
        </x-slot>
    @endif
</flux:callout>