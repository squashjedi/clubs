<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Member;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;

new class extends Component {
    public Club $club;

    public Member $member;

    public bool $isAssigned = false;

    public bool $isArchived = false;

    public function mount()
    {
        $this->isAssigned = $this->member->isAssigned();
        $this->isArchived = $this->member->trashed();
    }

    #[Renderless]
    public function delete()
    {
        abort_if($this->memberHasCompeted(), 403);

        $this->dispatch('delete');

        Flux::modal("delete-member-{$this->member->id}")->close();

        $this->member->permanentlyDelete();

        Flux::toast(
            variant: 'success',
            text: "{$this->member->full_name} deleted."
        );
    }

    #[Computed]
    public function memberHasCompeted()
    {
        return $this->member->hasCompeted();
    }
}; ?>

<flux:table.row id="row-{{ $member->id }}" style="height:65px;" class="{{ $member->trashed() ? 'bg-archived' : '' }}">
    <flux:table.cell>{{ $member->club_member_id }}</flux:table.cell>
    <flux:table.cell>
        <div class="flex items-center gap-1">
            {{ $member->full_name }}
            <div x-cloak x-data="{ isAssigned: $wire.isAssigned }" x-on:user-removed.window="isAssigned = false">
                <flux:icon.user x-show="isAssigned" variant="solid" class="size-4 text-green-600" />
            </div>
        </div>
    </flux:table.cell>
    <flux:table.cell>
        <livewire:components.buttons.invite-user-as-club-member-button :$club :$member>
    </flux:table.cell>
    <flux:table.cell align="end">
        <flux:button href="{{ route('club.admin.members.edit', [$club, $member]) }}" icon="pencil-square" icon:variant="outline" size="sm" variant="subtle" wire:navigate />

        <!-- Delete -->
        @if (! $this->memberHasCompeted)
            <flux:modal.trigger name="delete-member-{{ $member->id }}">
                <flux:button icon="trash" icon:variant="outline" size="sm" variant="subtle" />
            </flux:modal.trigger>

            @teleport('body')
                <flux:modal name="delete-member-{{ $member->id }}" class="modal">
                    <form wire:submit="delete">
                        <x-modals.content>
                            <x-slot:heading>{{ __('Delete') }} {{ __('Member') }}</x-slot:heading>
                                Are you sure you wish to permanently delete {{ $member->full_name }}?
                            <x-slot:buttons>
                                <flux:button type="submit" variant="danger">Delete</flux:button>
                            </x-slot:buttons>
                        </x-modals.content>
                    </form>
                </flux:modal>
            @endteleport
        @endif
    </flux:table.cell>
</flux:table.row>

@script
<script>
    $js('delete', (id, name) => {
        document.getElementById(`row-${id}`).style.display = 'none'

        $wire.delete()

        Flux.modal(`delete-member-${id}`).close()

        Flux.toast({
            variant: 'success',
            text: `${name} permanently deleted.`
        });
    })
</script>
@endscript