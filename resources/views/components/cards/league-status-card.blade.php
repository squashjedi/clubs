@props([
    'name',
    'leagueCount',
    'club',
    'leagues',
    'hasLeagueSessions' => true,
    'isArchived' => false,
    'expanded' => false,
    'showTag' => false,
    'color' => 'default',
    'info' => false,
])

<flux:tab.panel name="{{ $name }}">

    <div class="space-y-3">
        @if ($info)
            <flux:text>{{ $info }}</flux:text>
        @endif
        <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 mt-6">
            @foreach ($leagues as $league)
                <a
                    href="{{
                        $isArchived ?
                            route('club.admin.leagues.edit', [$club, $league]) :
                            ($hasLeagueSessions ?
                                route('club.admin.leagues.sessions.show', [$club, $league, 'session' => $league->latestSession]) :
                                route('club.admin.leagues.sessions.create', [$club, $league]))
                    }}"
                    wire:navigate
                >
                    <flux:callout class="hover:bg-stone-50">
                        <flux:callout.heading class="">{{ $league->name }}</flux:callout.heading>
                        @if ($hasLeagueSessions && ! $isArchived)
                            <flux:callout.text class="">{{ $league->latestSession->activePeriod }}</flux:callout.text>
                                <x-tags.session-status-tag class="" :session="$league->latestSession" />
                        @endif
                    </flux:callout>
                </a>
            @endforeach
        </div>
    </div>
</flux:tab.panel>