<div
    x-data="{
        memberId: {{ json_encode($entrant->member_id) }},
        originalMemberIds: {{ json_encode($originalMemberIds) }}
    }"
    x-ref="entrant-{{ $entrant->id }}"
    {{ $attributes }}
>
    @php
        $isEntrantMemberTrashed = $entrant->member->trashed();
    @endphp
    <div
        @class([
            'flex items-center justify-between gap-4 px-1.5 py-1.5 bg-white rounded-lg border shadow-xs min-h-10' => true,
            '!shadow-none' => ! $editable
        ])
    >
        <div class="flex items-center gap-1">
            @if ($editable)
                <flux:button
                    x-on:mousedown="$wire.openCompetitorListId = -1"
                    size="xs"
                    icon="grip"
                    icon:variant="micro"
                    variant="subtle"
                    class="!shrink-0"
                    x-sort:handle
                />
            @else
                <flux:spacer class="h-6" />
            @endif

            <div class="text-zinc-500 w-9 ml-1 mr-2">#{{ $entrant->index + 1 }}</div>

            <x-generic.member
                :club="$entrant->member->club"
                :member="$entrant->member"
            />
            @if ($hasPreviousSession)
                <flux:tooltip x-show="! originalMemberIds.find(originalMemberId => memberId === originalMemberId)">
                    <flux:icon.sparkles
                        variant="micro"
                        class="text-indigo-500"
                    />
                    <flux:tooltip.content class="tooltip">
                        New (not in previous session)
                    </flux:tooltip.content>
                </flux:tooltip>
            @endif
        </div>
        @if ($editable)
            <div class="flex items-center gap-2">
                @if ($isAllocated)
                    <flux:tooltip>
                        <flux:icon.check-circle
                            variant="micro"
                            class="text-green-500"
                        />
                        <flux:tooltip.content class="tooltip">Entrant has been added to the structure.</flux:tooltip.content>
                    </flux:tooltip>
                @else
                    <flux:tooltip>
                        <flux:icon.circle-dashed
                                variant="micro"
                                class="text-gray-400"
                            />
                        <flux:tooltip.content class="tooltip">Entrant has not been added to the structure.</flux:tooltip.content>
                    </flux:tooltip>
                @endif
                    <flux:button
                        size="xs"
                        :loading="false"
                        wire:click="remove({{ $entrant->id }})"
                        icon="trash"
                        icon:variant="outline"
                        variant="subtle"
                    />
            </div>
        @endif
    </div>
</div>