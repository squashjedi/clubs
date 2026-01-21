@props([
    'player'
])

<span class="text-sm">
    <span class="text-zinc-700 font-medium">{{ $player['first_name'] }} {{ $player['last_name'] }}</span>
    @if ($player['members'][0])
        <span class="text-zinc-500 text-xs">M{{ $player['members'][0]['club_member_id'] }}</span>
    @endif
    @if ($player['has_user'])
        <flux:icon.user variant="mini" class="inline-block text-green-600 size-4" />
    @endif
</span>