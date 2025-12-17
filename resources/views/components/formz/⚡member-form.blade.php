<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\User;
use App\Enums\Gender;
use App\Models\Member;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Enums\PlayerRelationship;
use Livewire\Attributes\Computed;
use App\Livewire\Forms\MemberForm;

new class extends Component {
    public Club $club;

    public ?Member $member = null;

    public MemberForm $form;

    public $isEdit = false;

    public bool $showRestoreModal = false;

    public bool $showDeleteModal = false;

    public bool $showArchiveModal = false;

    public function mount()
    {
        if ($this->isEdit) {
            $this->form->setMember($this->member);
        }
    }

    public function restore()
    {
        $this->member->restore();

        $this->showRestoreModal = false;

        Flux::toast(
            variant: "success",
            text: "Member restored."
        );
    }

    public function archive()
    {
        $this->member->delete();

        $this->showArchiveModal = false;

        Flux::toast(
            variant: "success",
            text: "Member archived."
        );
    }

    public function delete()
    {
        $this->member->permanentlyDelete();

        $this->showDeleteModal = false;

        Flux::toast(
            variant: "success",
            text: "{$this->member->full_name} deleted."
        );

        $this->redirectRoute('club.admin.members', ['club' => $this->club], navigate: true);
    }

    public function save()
    {
        if (! $this->isEdit) {
            $this->form->store($this->club);

            Flux::toast(
                variant: 'success',
                text: 'Member created.'
            );

            $this->redirectRoute('club.admin.members', ['club' => $this->club], navigate: true);

            return;
        }

        $this->form->update();

        Flux::toast(
            variant: 'success',
            text: 'Member updated.'
        );
    }

    #[On('user-removed')]
    public function refresh()
    {
        $this->member->refresh();
    }

    public function with(): array
    {
        return [
            'hasUser' => $this->member?->hasUser(),
        ];
    }
}; ?>

<div class="space-y-main">
    <x-headings.page-heading>
        @if (empty($member))
            Create New Member
        @else
            <div class="sm:flex sm:flex-row-reverse sm:items-center sm:justify-end sm:gap-2 space-y-1 sm:space-y-0">
                @if ($member->trashed())
                    <flux:badge color="red" variant="solid">{{ __('Archived') }}</flux:badge>
                @endif
                <div>Edit Member: {{ $member->club_member_id }}</div>
            </div>
        @endif
    </x-headings.page-heading>

    <form wire:submit="save" class="space-y-6">

        @if ($isEdit)
                @if (! $hasUser)
                    <flux:card class="!border-l-0 !border-r-0 !rounded-none !px-0">
                        <flux:field>
                            <flux:label>
                                Invitation for {{ $member->player->name }} (or a guardian of {{ $member->player->name }}) to take ownership.
                            </flux:label>
                            <livewire:buttons.invite-user-as-club-member-button :$club :member="$form->member" :$hasUser />
                        </flux:field>
                    </flux:card>
                @else
                    <flux:callout icon="exclamation-triangle" variant="secondary">
                        <flux:callout.text>
                            @if ($member->guardian)
                                {{ $member->guardian->name }} (Player ID: {{ $member->guardian->player_id }}) is acting as the guardian for {{ $member->player->name }} so only {{ $member->guardian->name }} can update these details.
                            @else
                                Only {{ $member->player->name }} can update these details.
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
                        <flux:table.cell class="!font-medium !text-zinc-700">{{ $member->player->name }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="!w-0">Gender</flux:table.cell>
                        <flux:table.cell class="!font-medium !text-zinc-700">{{ $member->player->gender->label() }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="!w-0">Date of Birth</flux:table.cell>
                        <flux:table.cell class="!font-medium !text-zinc-700">{{ $member->player->dob ? $member->player->dob : 'Unknown' }}</flux:table.cell>
                    </flux:table.row>
                    @php
                        $original = $member->player->email;
                        $masked = str_repeat('•', Str::length($original));
                    @endphp
                    <flux:table.row>
                        <flux:table.cell class="!w-0">Email</flux:table.cell>
                        <flux:table.cell class="!font-medium !text-zinc-700">{{ $masked }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell class="!w-0">Tel No</flux:table.cell>
                        <flux:table.cell class="!font-medium !text-zinc-700">{{ $member->player->tel_no }}</flux:table.cell>
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
                        x-ref="first_name"
                        x-init="$refs.first_name.focus()"
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
                        @foreach (Gender::cases() as $gender)
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
                        @if ($member->trashed())
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

                        <flux:menu.separator />

                        <flux:menu.item
                            x-on:click="$wire.showDeleteModal = true"
                            variant="danger"
                            icon="trash"
                            icon:variant="outline"
                        >
                            Delete
                            @teleport('body')
                                <flux:modal wire:model.self="showDeleteModal" class="modal">
                                    <form wire:submit="delete">
                                        <x-modals.content>
                                            <x-slot:heading>{{ __('Delete') }} {{ __('Member') }}</x-slot:heading>
                                            Are you sure you wish to permanently delete this member?
                                            <x-slot:buttons>
                                                <flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button>
                                            </x-slot:buttons>
                                        </x-modals.content>
                                    </form>
                                </flux:modal>
                            @endteleport
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            @endif
        </div>
    </form>
</div>