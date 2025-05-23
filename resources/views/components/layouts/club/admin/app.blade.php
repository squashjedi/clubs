<x-layouts.app>
    <flux:sidebar sticky stashable class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center justify-between">
            <div>
                <flux:brand :href="route('dashboard')" logo="https://fluxui.dev/img/demo/logo.png" name="{{ config('app.name') }}" class="px-2 dark:hidden" wire:navigate />
                <flux:brand :href="route('dashboard')" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="{{ config('app.name') }}" class="px-2 hidden dark:flex" wire:navigate />
            </div>
            <div class="lg:hidden">
                <flux:sidebar.toggle variant="ghost" icon="x-mark" class="cursor-pointer" />
            </div>
        </div>

        <flux:navlist variant="outline">
            <flux:navlist.item icon="home" :href="route('clubs.admin', [ $club ])" :current="request()->routeIs('clubs.admin')" wire:navigate>Dashboard</flux:navlist.item>
            <flux:navlist.item icon="settings-2" :href="route('clubs.admin.profile', [ $club ])" :current="request()->routeIs('clubs.admin.profile')" wire:navigate>Profile</flux:navlist.item>
            <flux:navlist.item icon="users" :href="route('clubs.admin.members', [ $club ])" :current="request()->routeIs('clubs.admin.members') || request()->routeIs('clubs.admin.members.*')" wire:navigate>Members</flux:navlist.item>
            <flux:navlist.item icon="rows-3" :href="route('clubs.admin.leagues', [ $club ])" :current="request()->routeIs('clubs.admin.leagues') || request()->routeIs('clubs.admin.leagues.*')" wire:navigate>Leagues</flux:navlist.item>
        </flux:navlist>

        <flux:spacer />

        <x-auth-dropdown-menu sidebar />
    </flux:sidebar>

    <flux:header class="lg:hidden border-b min-h-16">
        <div class="flex items-center gap-4 py-2">
            <x-ui.typography.h2 class="!pb-0">{{ $club->name }}</x-ui.typography.h2>
            <flux:button
                variant="filled"
                size="sm"
                :href="route('clubs.front', [ $club ])"
                icon-trailing="eye"
                wire:navigate
            >View</flux:button>
        </div>

        <flux:spacer />

        <flux:sidebar.toggle class="lg:hidden ml-6 lg:ml-8" variant="filled" icon="menu" inset="left" class="cursor-pointer" />
    </flux:header>

    <flux:main class="">
        <div class="-mx-6 lg:-mx-8 -mt-8 pt-4 mb-6 hidden lg:block">
            {{-- <flux:separator class="-mt-6" /> --}}
            <div class="flex items-center gap-4 pb-4 px-6 lg:px-8">
                <x-ui.typography.h2 class="!pb-0">{{ $club->name }}</x-ui.typography.h2>
                <flux:button
                    variant="filled"
                    size="sm"
                    :href="route('clubs.front', [ $club ])"
                    icon-trailing="eye"
                    wire:navigate
                >View</flux:button>
            </div>
            <flux:separator />
        </div>
{{--         <flux:heading size="xl" level="1">Good afternoon, Olivia</flux:heading>

        <flux:subheading size="lg" class="mb-6">Here's what's new today</flux:subheading> --}}


        <div class="w-full max-w-3xl">
            {{ $slot }}
        </div>
    </flux:main>


    {{-- <div x-data="{ open: false }">
        <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
        <div
            x-show="open"
            class="relative z-50 lg:hidden" role="dialog" aria-modal="true"
        >
            <div
                x-show="open"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900/80" aria-hidden="true"
            ></div>
            <div class="fixed inset-0 flex">
                <div
                    x-on:click.outside="open = false"
                    x-show="open"
                    x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="relative mr-16 flex w-full max-w-xs flex-1"
                >
                    <div
                        x-show="open"
                        x-transition:enter="ease-in-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in-out duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute top-0 left-full flex w-16 justify-center pt-5"
                    >
                        <button x-on:click="open = false" type="button" class="-m-2.5 p-2.5">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Sidebar component, swap this element with another sidebar if you like -->
                    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4 ring-1 ring-white/10">
                        <div class="flex h-16 shrink-0 items-center">
                            <img class="h-8 w-auto" src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company">
                        </div>
                        <nav class="flex flex-1 flex-col">
                            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                <li>
                                    <ul role="list" class="-mx-2 space-y-1">
                                        <li>
                                            <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                                            <a href="#" class="group flex gap-x-3 rounded-md bg-gray-800 p-2 text-sm/6 font-semibold text-white">
                                                <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                                </svg>
                                                Dashboard
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <img class="h-8 w-auto" src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company">
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                                    <a :href="route('clubs.admin.dashboard', [ $club ])" class="group flex gap-x-3 rounded-md bg-gray-800 p-2 text-sm/6 font-semibold text-white" wire:navigate>
                                        <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                        </svg>
                                        Dashboard
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <div class="lg:pl-72">
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-xs sm:gap-x-6 sm:px-6 lg:px-8">
                <button x-on:click="open = true" type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden">
                <span class="sr-only">Open sidebar</span>
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                </button>
                <!-- Separator -->
                <div class="h-6 w-px bg-gray-900/10 lg:hidden" aria-hidden="true"></div>
                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex-1 grid-cols-1 flex items-center">{{ $club->name }}</div>
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-900/10" aria-hidden="true"></div>
                        <!-- Profile dropdown -->
                        <div class="relative">
                            <flux:dropdown position="bottom" align="start">
                                <flux:profile
                                    :name="auth()->user()->full_name"
                                    :initials="auth()->user()->initials()"
                                    icon-trailing="chevrons-up-down"
                                />
                                <flux:menu class="w-[220px]">
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
                                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                                    </flux:menu.radio.group>
                                    <flux:menu.separator />
                                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                                        @csrf
                                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                        {{ __('Log Out') }}
                                        </flux:menu.item>
                                    </form>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            </div>
            <main class="py-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div> --}}
</x-layouts.app>