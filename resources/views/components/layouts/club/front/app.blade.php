<x-layouts.app>
    <x-layouts.header.main />

    <flux:main
        container
        x-data="{ open: false }"
    >
        <div class="-mt-2 lg:-mt-4 mb-6">
            <div class="flex items-center justify-between mb-4 md:mb-6 md:mt-2">
                <div class="flex items-center gap-3">
                    <x-ui.typography.h2 class="!pb-0">{{ $club->name }}</x-ui.typography.h2>

                    @can('view', $club)
                        <x-clubs.admin-button :$club />
                    @endcan
                </div>

                <div class="flex items-center gap-2">

                    <livewire:clubs.front.follow :$club />

                    <flux:button x-show="open" icon="x" size="sm" x-on:click="open = !open" class="ml-4 md:hidden px-2" />
                    <flux:button x-show="! open" icon="menu" size="sm" x-on:click="open = !open" class="ml-4 md:hidden px-2" />
                </div>
            </div>
            <flux:separator />
        </div>
        <div class="flex max-md:flex-col items-start">
            <div class="hidden md:block w-full md:w-[200px] pb-4 mr-10">
                <x-layouts.club.front.menu :$club />
            </div>
            <div x-show="open" class="md:hidden w-full pb-4 mr-10">
                <x-layouts.club.front.menu :$club class="-mt-2 mb-4" />
                <flux:separator />
            </div>

            <div class="w-full max-w-3xl">
                {{ $slot }}
            </div>
        </div>
    </flux:main>
</x-layouts.app>