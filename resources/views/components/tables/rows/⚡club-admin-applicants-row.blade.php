<?php

use Flux\Flux;
use App\Models\Club;
use App\Enums\Gender;
use App\Models\Member;
use App\Models\Player;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;

new class extends Component {
    public Club $club;

    public Member $member;

    public Player $player;

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
            text: "{$this->member->name} deleted."
        );
    }

    #[Computed]
    public function memberHasCompeted()
    {
        return $this->member->hasCompeted();
    }
}; ?>

<flux:table.row id="row-{{ $member->id }}" style="height:65px;" class="{{ $member->trashed() ? 'bg-archived' : '' }}">
    <flux:table.cell>
        <div class="flex items-center gap-1">
            {{ $player->name }}
            @if ($member->trashed())
                <flux:icon.no-symbol
                    variant="micro"
                    class="text-red-500 inline-block"
                />
            @endif
        </div>
    </flux:table.cell>
    <flux:table.cell>
        @if ($player->gender !== Gender::Unknown)
            <div class="flex items-center gap-1">
                <flux:icon
                    name="{{ $player->gender->icon() }}"
                    class="{{ $player->gender->color() }} size-5"
                />
                <span>{{ $player->gender->label() }}</span>
            </div>
        @else
            -
        @endif
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
                                Are you sure you wish to permanently delete {{ $member->name }}?
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