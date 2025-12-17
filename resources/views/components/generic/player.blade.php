@props([
    'name',
    'memberId',
    'hasUser',
])

<div>
    <div class="flex items-center gap-1">
        <div>{{ $name }}</div>
        <div class="text-xs">M{{ $memberId }}</div>
        @if ($hasUser)
            <flux:icon.user variant="mini" class="size-4 text-green-600 inline-block" />
        @endif
        <div>WD</div>
    </div>

    @if ($telNo)
        <flux:description class="flex items-center gap-0.5 text-xs">
            <flux:icon.phone
                variant="micro"
                class="size-3 text-zinc-500 inline-block"
            />
            <span class="text-zinc-500 font-light text-xs">{{ $member->tel_no }}</span>
            @if ($member->users()->first()->pivot->relationship === PlayerRelationship::Guardian)
                ({{ $member->users()->first()->name }})
            @endif
        </flux:description>
    @endif
</div>