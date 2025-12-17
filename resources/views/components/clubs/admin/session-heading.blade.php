<div class="relative flex items-center justify-between h-8">
    <x-ui.typography.h4>{{ $session->active_period }}</x-ui.typography.h4>
    @if ($session->id !== $league->latestSession->id)
        <flux:button href="{{ route('clubs.backend.leagues.sessions.edit', [ $club, $league, 'session' => $league->latestSession ]) }}" icon:trailing="square-arrow-out-up-right" icon:variant="outline" size="sm" wire:navigate>Latest Session</flux:button>
    @endif
</div>