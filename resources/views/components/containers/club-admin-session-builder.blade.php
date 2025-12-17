<x-containers.club-admin-session :$club :$league :$session>

    <div class="space-y-2">
        <livewire:headings.session-builder-heading :$club :$league :$session />

        <div class="space-y-6">
            <flux:navbar class="border-b">
                <flux:navbar.item href="{{ route('club.admin.leagues.sessions.entrants', [$club, $league, $session]) }}" :current="request()->routeIs('club.admin.leagues.sessions.entrants')" wire:navigate>Entrants</flux:navbar.item>
                <flux:navbar.item href="{{ route('club.admin.leagues.sessions.structure', [$club, $league, $session]) }}" :current="request()->routeIs('club.admin.leagues.sessions.structure')" wire:navigate>Structure</flux:navbar.item>
            </flux:navbar>

            {{ $slot }}
        </div>
    </div>
</x-containers.club-admin-session>