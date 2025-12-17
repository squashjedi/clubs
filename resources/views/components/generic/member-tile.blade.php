@props([
    'member'
])

<span>
    <span>{{ $member->player->name }}</span>
    <span class="text-zinc-500 text-xs">M{{ $member->club_member_id }}</span>
    @if ($member->player->has_user)
        <flux:icon.user variant="mini" class="inline-block text-green-600 size-4" />
    @endif
</span>