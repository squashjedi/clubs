@props([
    'heading',
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

<flux:accordion.item :disabled="$leagueCount === 0" :expanded="$expanded">
    <flux:accordion.heading
        @class([
            'text-zinc-500 font-normal' => $leagueCount === 0,
        ])
    >
        {{ $heading }} ({{ $leagueCount }})
    </flux:accordion.heading>

    <flux:accordion.content class="space-y-3">
        @if ($info)
            <div>{{ $info }}</div>
        @endif
        <div class="grid gap-3">
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
                    <flux:callout color="{{ $color }}" class="shadow-sm hover:shadow-none">
                        <flux:callout.heading>{{ $league->name }}</flux:callout.heading>
                        @if ($hasLeagueSessions && ! $isArchived)
                            <flux:callout.heading class="">{{ $league->latestSession->activePeriod }}</flux:callout.heading>
                            @if ($showTag)
                                <x-tags.session-status-tag class="" :session="$league->latestSession" />
                            @endif
                        @endif
                    </flux:callout>
                </a>
            @endforeach
        </div>
    </flux:accordion.content>
</flux:accordion.item>