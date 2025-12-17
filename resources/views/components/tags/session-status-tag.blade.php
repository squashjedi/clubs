@php
use Carbon\CarbonInterface;
@endphp

@props([
    'session',
])

<div>
    @if (is_null($session->published_at))
        <flux:badge size="sm">Draft</flux:badge>
    @endif

    @if (! is_null($session->published_at) && now() < $session->starts_at)
        <flux:badge color="amber" size="sm">Starts in {{ $session->starts_at->timezone($session->timezone)->diffForHumans(now(), ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'minimumUnit' => 'minute'], false, 2) }}</flux:badge>
    @endif

    @if (! is_null($session->published_at) && now() > $session->starts_at && now() <= $session->ends_at)
        <flux:badge color="green" size="sm">Ends in {{ $session->ends_at->timezone($session->timezone)->diffForHumans(now(), ['syntax' => CarbonInterface::DIFF_ABSOLUTE, 'minimumUnit' => 'minute'], false, 2) }}</flux:badge>
    @endif

    @if (is_null($session->processed_at) && isset($session->published_at) && now() > $session->ends_at)
        <flux:badge color="red" size="sm">Waiting to be processed</flux:badge>
    @endif

    @if (! is_null($session->processed_at))
        <flux:badge color="blue" size="sm">Processed</flux:badge>
    @endif
</div>