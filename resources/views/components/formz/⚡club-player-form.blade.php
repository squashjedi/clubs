<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\User;
use App\Enums\Gender;
use App\Models\Player;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Enums\PlayerRelationship;
use Livewire\Attributes\Computed;
use App\Livewire\Forms\MemberForm;
use App\Livewire\Forms\ClubPlayerForm;
use App\Actions\Players\MergePlayersForClubAction;

new class extends Component {
    public Club $club;

    public ?Player $player = null;

    public ?int $playerToMergeId = null;

    public ClubPlayerForm $form;

    public $isEdit = false;

    public bool $showRestoreModal = false;

    public bool $showDeleteModal = false;

    public bool $showArchiveModal = false;

    public function mount()
    {
        if ($this->isEdit) {
            $this->form->setPlayer($this->player);
        }
    }

    public function restore()
    {
        $this->player->restoreInClub($this->club);

        $this->showRestoreModal = false;

        $this->resetPlayer();

        Flux::toast(
            variant: "success",
            text: "Member restored."
        );
    }

    public function archive()
    {
        $this->player->archiveInClub($this->club);

        $this->showArchiveModal = false;

        $this->resetPlayer();

        Flux::toast(
            variant: "success",
            text: "Member archived."
        );
    }

    protected function resetPlayer()
    {
        $this->player = $this->club->players()->find($this->player->id);
    }

    public function save()
    {
        if (! $this->isEdit) {
            $this->form->store($this->club);

            Flux::toast(
                variant: 'success',
                text: 'Player created.'
            );

            $this->redirectRoute('club.admin.players', ['club' => $this->club], navigate: true);

            return;
        }

        $this->form->update();

        $this->resetPlayer();

        // $this->redirectRoute('club.admin.players.edit', ['club' => $this->club, 'player' => $this->player], navigate: true);

        Flux::toast(
            variant: 'success',
            text: 'Member updated.'
        );
    }

    public function merge()
    {
        $duplicatePlayer = Player::findOrFail($this->playerToMergeId);

        app(MergePlayersForClubAction::class)->execute($this->club, $this->player, $duplicatePlayer);

        $this->resetPlayer();

        Flux::toast(
            variant: 'success',
            text: 'Members merged.'
        );
    }

    #[On('user-removed')]
    public function refresh()
    {
        $this->player->refresh();
    }

    public function with(): array
    {
        return [
            'isTrashed' => ! is_null($this->player?->pivot?->deleted_at),
            'hasUser' => $this->player?->users()->exists(),
        ];
    }
}; ?>

<div class="space-y-main">
    <x-headings.page-heading>
        @if (empty($player))
            Create New Member
        @else
            <div class="sm:flex sm:flex-row-reverse sm:items-center sm:justify-end sm:gap-2 space-y-1 sm:space-y-0">
                @if ($isTrashed)
                    <flux:badge color="red" variant="solid">{{ __('Archived') }}</flux:badge>
                @endif
                <div>Edit Member: {{ $player->pivot->club_player_id }}</div>
            </div>
        @endif
    </x-headings.page-heading>

    <x-ui.cards.mobile>
        <form wire:submit="save" class="space-y-6">

            @if ($isEdit)
                    @if (! $hasUser)
                        <flux:card class="!bg-zinc-50">
                            <flux:field>
                                <flux:label>
                                    Invitation for {{ $player->name }} (or a guardian of {{ $player->name }}) to take ownership.
                                </flux:label>
                                <livewire:buttons.invite-club-player-button :$club :player="$form->player" />
                            </flux:field>
                        </flux:card>
                    @else
                        <flux:callout icon="exclamation-triangle" variant="secondary">
                            <flux:callout.text>
                                @if ($player->guardian)
                                    {{ $player->guardian->name }} is acting as the guardian for {{ $player->name }} so only {{ $player->guardian->name }} can update these details.
                                @else
                                    Only {{ $player->name }} can update these details.
                                @endif
                            </flux:callout.text>
                        </flux:callout>
                    @endif
            @endif

            @if ($hasUser)
                <flux:table>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell class="!w-0">Name</flux:table.cell>
                            <flux:table.cell class="!font-medium !text-zinc-700">{{ $player->name }}</flux:table.cell>
                        </flux:table.row>
                        <flux:table.row>
                            <flux:table.cell class="!w-0">Gender</flux:table.cell>
                            <flux:table.cell class="!font-medium !text-zinc-700">
                                <x-labels.gender-label :gender="$player->gender" />
                            </flux:table.cell>
                        </flux:table.row>
                        <flux:table.row>
                            <flux:table.cell class="!w-0">Date of Birth</flux:table.cell>
                            <flux:table.cell class="!font-medium !text-zinc-700">{{ $player->dob ? $player->dob->format('d M Y') : '-' }}</flux:table.cell>
                        </flux:table.row>
                        <flux:table.row>
                            <flux:table.cell class="!w-0">Email</flux:table.cell>
                            <flux:table.cell class="!font-medium !text-zinc-700">{{ str_repeat('•', Str::length($player->email)) }}</flux:table.cell>
                        </flux:table.row>
                        <flux:table.row>
                            <flux:table.cell class="!w-0">Tel No</flux:table.cell>
                            <flux:table.cell class="!font-medium !text-zinc-700">{{ $player->tel_no ?? '-' }}</flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="space-y-6">
                    <div class="sm:flex sm:items-start gap-x-6 space-y-6 sm:space-y-0">
                        <flux:input
                            wire:model="form.first_name"
                            label="First Name"
                            class="max-w-sm"
                            :disabled="$hasUser"
                        />
                        <flux:input wire:model="form.last_name" label="Last Name" class="max-w-sm"  :disabled="$hasUser" />
                    </div>

                    <flux:fieldset>
                        <flux:label
                            @class([
                                '!opacity-50' => $hasUser,
                                'mb-3',
                            ])
                        >
                            Gender
                        </flux:label>

                        <flux:radio.group
                            wire:model="form.gender"
                            variant="cards"
                            :indicator="false"
                            :disabled="$hasUser"
                            class="max-w-sm"
                        >
                            @foreach (collect(Gender::cases())->reject(fn($g) => $g === Gender::Unknown) as $gender)
                                <flux:radio value="{{ $gender->value }}" label="{{ $gender->name }}" class="text-center" />
                            @endforeach
                        </flux:radio.group>
                    </flux:fieldset>

                    <flux:date-picker wire:model="form.dob" selectable-header clearable label="Date of Birth" class="max-w-xs" :disabled="$hasUser" />

                    @if ($hasUser)
                        <flux:input
                            value="form.email"
                            type="password"
                            label="Email"
                            class="max-w-sm"
                            disabled
                        />
                    @else
                        <flux:input wire:model="form.email" label="Email" class="max-w-sm" />
                    @endif

                    <flux:input wire:model="form.tel_no" label="Tel No" class="max-w-sm" :disabled="$hasUser" />
                </div>
            @endif

            <div class="flex gap-2">

                @if ($isEdit)
                    @if (! $hasUser)
                        <flux:button type="submit" variant="primary">{{ __('Update') }}</flux:button>
                    @endif

                    <flux:dropdown>
                        <flux:tooltip content="Options">
                            <flux:button icon:trailing="ellipsis-horizontal" square />
                        </flux:tooltip>

                        <flux:menu>
                            @if ($isTrashed)
                                <flux:menu.item
                                    wire:click="restore"
                                    icon="arrow-path"
                                    icon:variant="outline"
                                >
                                    {{ __('Restore') }}
                                </flux:menu.item>
                            @else
                                <flux:menu.item
                                    wire:click="archive"
                                    icon="no-symbol"
                                    icon:variant="outline"
                                >
                                    {{ __('Archive') }}
                                </flux:menu.item>
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                @else
                    <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
                @endif
            </div>
        </form>
    </x-ui.cards.mobile>
</div>