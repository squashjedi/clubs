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

    <div class="space-y-8">
        {{ $slot }}
    </div>
</x-containers.club-admin-session>