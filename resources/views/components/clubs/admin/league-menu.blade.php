<div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
        <x-ui.typography.h3>{{ $league->name }}</x-ui.typography.h3>
        <flux:button href="{{ route('clubs.admin.leagues.edit', [ $club, $league ]) }}" icon="pencil-square" variant="filled" wire:navigate />
    </div>
    <flux:navbar>
        <flux:navbar.item href="#">Latest</flux:navbar.item>
        <flux:navbar.item href="{{ route('clubs.admin.leagues.sessions', [ $club, $league ]) }}" :current="request()->routeIs('clubs.admin.leagues.sessions') || request()->routeIs('clubs.admin.leagues.sessions.*')" wire:navigate>Sessions</flux:navbar.item>
    </flux:navbar>
</div>