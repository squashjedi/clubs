@props([
    'club',
    'league',
    'session',
    'tier',
    'division'
])

<x-containers.club-admin-session :$club :$league :$session :showNavbar="true">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-1 h-10">
            <flux:icon.arrow-left variant="micro" class="size-3 sm:size-4 text-zinc-500" />
            <flux:link
                variant="subtle"
                href="{{ route('club.admin.leagues.sessions.tables', [$club, $league, $session]) }}"
                wire:navigate
            >
                <div>{{ __('Back to Tables') }}</div>
            </flux:link>
        </div>
        <livewire:dropdowns.hierarchy :$club :$league :$session :$division />
    </div>

    <div class="space-y-8 mt-8">
        <div class="relative border-t">
            <div class="sm:flex sm:items-center sm:gap-2 sm:justify-between space-y-4 sm:space-y-0 mt-8 min-h-10">
                <livewire:headings.division-heading :$session :$division />

                <div class="flex flex-col items-center">
                    <div class="flex items-center font-medium bg-zinc-800/5 dark:bg-white/10 h-10 p-1 rounded-lg">
                        <button
                            href="{{ route('club.admin.leagues.sessions.tables.division.table', ['club' => $club, 'league' => $league, 'session' => $session, 'tier' => $tier['id'], 'division' => $division['id']]) }}"
                            @class([
                                'bg-white hover:bg-white text-normal text-zinc-600 shadow-xs' => request()->routeIs('club.admin.leagues.sessions.tables.division.table'),
                                'text-zinc-500 hover:text-zinc-600' => ! request()->routeIs('club.admin.leagues.sessions.tables.division.table'),
                                'px-7 py-1.5 rounded-md cursor-pointer'
                            ])
                            wire:navigate
                        >
                            Table
                        </button>
                        <button
                            href="{{ route('club.admin.leagues.sessions.tables.division.matrix', ['club' => $club, 'league' => $league, 'session' => $session, 'tier' => $tier['id'], 'division' => $division['id']]) }}"
                            @class([
                                'bg-white hover:bg-white text-normal text-zinc-600 shadow-xs' => request()->routeIs('club.admin.leagues.sessions.tables.division.matrix'),
                                'text-zinc-500 hover:text-zinc-600' => ! request()->routeIs('club.admin.leagues.sessions.tables.division.matrix'),
                                'px-7 py-1.5 rounded-md cursor-pointer'
                            ])
                            wire:navigate
                        >
                            Results
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{ $slot }}
    </div>
</x-containers.club-admin-session>