<?php

use Livewire\Volt\Component;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
{
    public Collection $availableMembers;

    public array $selectedMember;
}; ?>

<div>
    <flux:select variant="listbox" wire:model.live="selectedMember.id" searchable clearable placeholder="Select..." class="">
        @foreach ($availableMembers as $member)
            <flux:select.option value="{{ $member->id }}" wire:key="{{ $member->id }}">{{ $member->full_name }}</flux:select.option>
        @endforeach
    </flux:select>
    {{ $selectedMember['id'] }}
</div>
