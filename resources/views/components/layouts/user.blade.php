<x-layouts.app>
    <x-layouts.header.main />

    <flux:main container>
        {{ $slot }}
    </flux:main>
</x-layouts.app>