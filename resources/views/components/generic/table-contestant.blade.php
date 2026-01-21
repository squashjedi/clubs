@props([
    'club',
    'contestant',
])

<div class="flex items-center justify-between gap-4">
    <div class="">
        <flux:heading class="flex items-center gap-1">
            @if ($contestant['is_member'])
                <flux:link href="{{ route('club.admin.players.edit', [$club, 'player' => $contestant['player_id']]) }}">
                    {{ $contestant['name'] }}
                    <span class="text-zinc-500 text-xs">
                        M{{ $contestant['club_member_id'] }}
                    </span>
                </flux:link>
            @else
                <span>{{ $contestant['name'] }}</span>
            @endif
            @if ($contestant['has_user'])
                <flux:icon.user variant="mini" class="size-4 text-green-600" />
            @endif
        </flux:heading>
    </div>
    <div class="flex items-center gap-2">
        @if ($contestant['trashed'])
            <flux:badge color="red" size="sm">WD</flux:badge>
        @endif
    </div>
</div>