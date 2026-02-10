<?php

use Flux\Flux;
use App\Models\Club;
use App\Enums\Gender;
use App\Models\Member;
use App\Models\Player;
use Livewire\Component;
use App\Enums\PlayerRelationship;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;

new class extends Component {
    public Club $club;

    public Player $player;

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
};
?>

<flux:table.row id="row-{{ $player->id }}" style="height:65px;">
    <flux:table.cell>{{ $player->pivot->club_player_id }}</flux:table.cell>
    <flux:table.cell>
        <div class="flex items-center gap-1">
            <div>{{ $player->name }}</div>
            @if (! is_null($player->pivot->deleted_at))
                <flux:icon.no-symbol
                    variant="micro"
                    class="size-4 text-red-600"
                />
            @endif
        </div>
        @if ($player->tel_no)
            <flux:description class="flex items-center gap-0.5 text-xs">
                <flux:icon.phone
                    variant="micro"
                    class="size-3 inline-block"
                />
                <span>
                    {{ $player->tel_no }}
                    @if ($player->users()->first()->pivot->relationship === PlayerRelationship::Guardian)
                        ({{ $player->users()->first()->name }})
                    @endif
                </span>
            </flux:description>
        @endif
    </flux:table.cell>
    <flux:table.cell>
        <x-labels.gender-label :gender="$player->gender" />
    </flux:table.cell>
    <flux:table.cell>
        <livewire:buttons.invite-club-player-button :$club :$player :isTableView="true" />
    </flux:table.cell>
    <flux:table.cell align="end">
        <flux:button
            href="{{ route('club.admin.players.edit', [$club, $player]) }}"
            icon="pencil-square"
            icon:variant="outline"
            size="sm"
            variant="subtle"
            wire:navigate
            x-on:click="const scroller = $el.closest('ui-table-scroll-area'); if (scroller) { scroller.scrollTo({ top: 0, left: 0 }); scroller.setAttribute('data-scroll-x', '0'); scroller.setAttribute('data-scroll-y', '0'); } window.scrollTo(0, 0)"
        />
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