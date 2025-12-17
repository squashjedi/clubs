<div>
    <flux:badge color="zinc">{{ $league->sport->name }}</flux:badge>
    <div class="flex items-center gap-2">
        <x-ui.typography.h3>{{ $league->name }}</x-ui.typography.h3>
        <flux:badge as="button" href="{{ route('clubs.backend.leagues.edit', [ $club, $league ]) }}" variant="solid" size="sm" color="zinc" wire:navigate>{{ __('Edit') }}</flux:badge>
    </div>
</div>