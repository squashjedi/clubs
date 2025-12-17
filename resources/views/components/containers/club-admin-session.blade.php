@props([
    'showNavbar' => false,
    'club',
    'league',
    'session',
])

<div class="space-y-main">
    <x-headings.league-with-sub-heading :$club :$league />

    <div class="space-y-main">
        @if (request()->routeIs('club.admin.leagues.sessions.entrants') || request()->routeIs('club.admin.leagues.sessions.structure') || request()->routeIs('club.admin.leagues.sessions.tables'))
            @php
                $previousSession = $session->previous();
                $nextSession = $session->next();
            @endphp
            @if ($previousSession || $nextSession)
                <div class="flex items-center gap-2 justify-between min-h-10">
                    @if ($previousSession)
                        <div class="flex items-center gap-1">
                            <flux:icon.arrow-left
                                variant="micro"
                                class="size-3 sm:size-4 text-zinc-500"
                            />
                            <flux:link
                                variant="subtle"
                                href="{{ route('club.admin.leagues.sessions.tables', [$club, $league, 'session' => $previousSession]) }}"
                                class="text-xs sm:text-sm"
                                wire:navigate
                            >
                                {{ $previousSession->activePeriod }}
                            </flux:link>
                        </div>
                    @else
                        <div></div>
                    @endif
                    @if ($nextSession)
                        <div class="flex items-center gap-1">
                            <flux:link
                                variant="subtle"
                                href="{{ route('club.admin.leagues.sessions.tables', [$club, $league, 'session' => $nextSession]) }}"
                                class="text-xs sm:text-sm"
                                wire:navigate
                            >
                                {{ $nextSession->activePeriod }}
                            </flux:link>
                            <flux:icon.arrow-right
                                variant="micro"
                                class="size-3 sm:size-4 text-zinc-500"
                            />
                        </div>
                    @else
                        <div></div>
                    @endif
                </div>
            @endif
        @endif

        <livewire:headings.session-active-period-heading :$club :$league :$session />

        <div class="space-y-main">
            {{ $slot }}
        </div>
    </div>
</div>