@props([
    'sidebar' => false,
])

<flux:dropdown position="top" align="end" {{ $attributes->merge(['class' => '']) }}>
    <flux:profile
        class="cursor-pointer"
        :initials="auth()->user()->initials()"
        name="{{ $sidebar ? auth()->user()->name : '' }}"
    />
    <flux:menu>
        <flux:menu.radio.group>
            <div class="p-0 text-sm font-normal">
                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                        <span
                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                        >
                            {{ auth()->user()->initials() }}
                        </span>
                    </span>

                    <div class="grid flex-1 text-left text-sm leading-tight">
                        <span class="truncate font-semibold">{{ auth()->user()->full_name }}</span>
                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                    </div>
                </div>
            </div>
        </flux:menu.radio.group>

        <flux:menu.separator />

        <flux:menu.radio.group>
            <flux:menu.item href="{{ route('dashboard') }}" icon="user-group" wire:navigate>{{ __('Your Clubs') }}</flux:menu.item>
        </flux:menu.radio.group>

        {{-- <flux:menu.separator /> --}}

        <flux:menu.radio.group>
            <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
        </flux:menu.radio.group>

        <flux:menu.separator />

        <livewire:auth.logout />
    </flux:menu>
</flux:dropdown>