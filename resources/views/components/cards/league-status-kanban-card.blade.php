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

<flux:kanban.column>
    <flux:kanban.column.header
        :heading="$name"
        :subheading="$info"
        :count="$leagueCount"
    />
    <flux:kanban.column.cards>
        @foreach ($leagues as $league)
            <flux:kanban.card
                as="button"
                href="{{
                    $isArchived ?
                        route('club.admin.leagues.edit', [$club, $league]) :
                        ($hasLeagueSessions ?
                            route('club.admin.leagues.sessions.show', [$club, $league, 'session' => $league->latestSession]) :
                            route('club.admin.leagues.sessions.create', [$club, $league]))
                }}"
                class="cursor-pointer"
                :heading="$league->name"
                wire:navigate
            >
                <x-slot name="footer">
                    @if ($hasLeagueSessions && ! $isArchived)
                        <div class="space-y-2">
                            <flux:text>{{ $league->latestSession->activePeriod }}</flux:text>
                            <x-tags.session-status-tag class="" :session="$league->latestSession" />
                        </div>
                    @endif
                </x-slot>
            </flux:kanban.card>
        @endforeach
    </flux:kanban.column.cards >
</flux:kanban.column>