@php
    use App\Enums\PlayerRelationship;
@endphp

@props([
    'class' => '',
    'club',
    'member',
    'memberId',
    'showUser' => true,
    'showTelNo' => false,
    'isLink' => true,
])

<div {{ $attributes->merge(['class' => "$class space-y-1"]) }}">
    <div>
        @if ($isLink)
            <flux:link
                href="{{ route('club.admin.players.edit', [$club, 'player' => $member]) }}"
                variant="default"
                wire:navigate
            >
                {{ data_get($member, 'first_name') }} {{ data_get($member, 'last_name') }}
            </flux:link>
        @else
            <span>{{ data_get($member, 'first_name') }} {{ data_get($member, 'last_name') }}</span>
        @endif
        <span class="text-gray-500 text-xs">M{{ $memberId }}</span>
        @if ($showUser && $member->users_exists)
            <flux:icon.user variant="mini" class="size-4 text-green-600 inline-block" />
        @endif
        @if (data_get($member, 'deleted_at'))
            <flux:tooltip>
                <flux:icon.no-symbol
                    variant="micro"
                    class="text-red-500 inline-block"
                />
                <flux:tooltip.content>This member has been archived.</flux:tooltip.content>
            </flux:tooltip>
        @endif
    </div>
    @if ($showTelNo && $member->tel_no)
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