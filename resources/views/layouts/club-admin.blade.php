<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}" class="dark h-full bg-white">
    <head>
        @include("partials.head")
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 text-sm">

        <el-dialog>
            <dialog id="sidebar" class="backdrop:bg-transparent lg:hidden">
                <el-dialog-backdrop class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>

                <div tabindex="0" class="fixed inset-0 flex focus:outline-none">
                    <el-dialog-panel
                        class="group/dialog-panel relative mr-16 flex w-full max-w-xs flex-1 transform transition duration-300 ease-in-out data-closed:-translate-x-full"
                    >
                        <div class="absolute top-0 left-full flex w-16 justify-center pt-5 duration-300 ease-in-out group-data-closed/dialog-panel:opacity-0">
                            <button type="button" command="close" commandfor="sidebar" class="-m-2.5 p-2.5">
                                <span class="sr-only">Close sidebar</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 text-white">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>

                        <!-- Sidebar component, swap this element with another sidebar if you like -->
                        <div
                            class="relative flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4 ring-1 ring-white/10 dark:before:pointer-events-none dark:before:absolute dark:before:inset-0 dark:before:bg-black/10"
                        >
                            <div class="relative flex h-16 shrink-0 items-center">
                                <div class="text-white">Reckify</div>
                            </div>
                            <nav class="relative flex flex-1 flex-col">
                                <ul role="list" class="flex flex-1 flex-col gap-y-3">
                                    <x-navlists.club-admin-navlist
                                        link="{{ route('club.admin', [$club]) }}"
                                        :current="request()->routeIs('club.admin')"
                                        icon="home"
                                        page="Dashboard"
                                    />
                                    <x-navlists.club-admin-navlist
                                        link="{{ route('club.admin.profile', [$club]) }}"
                                        :current="request()->routeIs('club.admin.profile')"
                                        icon="settings-2"
                                        page="Profile"
                                    />
                                    <x-navlists.club-admin-navlist
                                        link="{{ route('club.admin.players', [$club]) }}"
                                        :current="request()->routeIs('club.admin.players') || request()->routeIs('club.admin.players.*')"
                                        icon="users"
                                        page="Members"
                                    />
                                    <x-navlists.club-admin-navlist
                                        link="{{ route('club.admin.leagues', [$club]) }}"
                                        :current="request()->routeIs('club.admin.leagues') || request()->routeIs('club.admin.leagues.*')"
                                        icon="table-properties"
                                        rotate="180"
                                        page="Leagues"
                                    />
                                </ul>
                            </nav>
                        </div>
                    </el-dialog-panel>
                </div>
            </dialog>
        </el-dialog>

        <!-- Static sidebar for desktop -->
        <div class="hidden bg-gray-900 ring-1 ring-white/10 lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-black/10 px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <div class="text-white">Reckify</div>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <x-navlists.club-admin-navlist
                                    link="{{ route('club.admin', [$club]) }}"
                                    :current="request()->routeIs('club.admin')"
                                    icon="home"
                                    page="Dashboard"
                                />
                                <x-navlists.club-admin-navlist
                                    link="{{ route('club.admin.profile', [$club]) }}"
                                    :current="request()->routeIs('club.admin.profile')"
                                    icon="settings-2"
                                    page="Profile"
                                />
                                <x-navlists.club-admin-navlist
                                    link="{{ route('club.admin.players', [$club]) }}"
                                    :current="request()->routeIs('club.admin.players') || request()->routeIs('club.admin.players.*')"
                                    icon="users"
                                    page="Members"
                                />
                                <x-navlists.club-admin-navlist
                                    link="{{ route('club.admin.leagues', [$club]) }}"
                                    :current="request()->routeIs('club.admin.leagues') || request()->routeIs('club.admin.leagues.*')"
                                    icon="table-properties"
                                    rotate="180"
                                    page="Leagues"
                                />
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="lg:pl-72">
            <div
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-stone-100 px-4 shadow-xs sm:gap-x-6 sm:px-6 lg:px-8 dark:border-white/10 dark:bg-gray-900"
            >
                <button
                    type="button"
                    command="show-modal"
                    commandfor="sidebar"
                    class="-m-2.5 p-2.5 text-gray-700 hover:text-gray-900 lg:hidden dark:text-gray-400 dark:hover:text-white"
                >
                    <span class="sr-only">Open sidebar</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6">
                        <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>

                <!-- Separator -->
                <div aria-hidden="true" class="h-6 w-px bg-gray-900/10 lg:hidden dark:bg-white/10"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex items-center flex-1 text-lg font-medium gap-2">
                        <livewire:selects.club-admin-clubs-select :$club />
                        <flux:link
                            href="{{ route('club', [$club]) }}"
                            class="text-sm"
                            wire:navigate
                        >
                            Club Site
                        </flux:link>
                    </div>
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Separator -->
                        <div aria-hidden="true" class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-900/10 dark:lg:bg-gray-100/10"></div>

                        <!-- Profile dropdown -->
                        <x-auth-dropdown-menu />
                    </div>
                </div>
            </div>

            <main class="py-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-5xl">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>

        @fluxScripts
    </body>
</html>
