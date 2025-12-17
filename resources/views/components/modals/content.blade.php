@props([
    'buttons' => null
])

<div class="space-y-6">
    <div class="space-y-4">
        @if (isset($heading))
            <flux:heading size="lg" class="mb-6">
                {{ $heading }}
            </flux:heading>
        @endif
        <flux:text class="space-y-4">
            {{ $slot }}
        </flux:text>
    </div>

    @if ($buttons)
        <div class="flex gap-3">
            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            {{ $buttons }}
        </div>
    @endif
</div>