<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Member;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use App\Livewire\Forms\MemberForm;

new class extends Component {
    public Club $club;

    public ?Member $member;

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
        abort_if($this->memberHasCompeted, 403);

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

    #[Computed]
    public function memberHasCompeted()
    {
        return $this->member->hasCompeted();
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

    <x-containers.club-admin-form>
        <form wire:submit="save" class="space-y-6">
            <div class="sm:grid sm:grid-cols-2 gap-x-6 space-y-6 sm:space-y-0">
                <flux:input wire:model="form.first_name" label="First Name" class="max-w-sm" x-ref="first_name" x-init="$refs.first_name.focus()" />
                <flux:input wire:model="form.last_name" label="Last Name" class="max-w-sm" />
            </div>
            @if ($isEdit)
                <flux:field>
                    <flux:label badge="can submit results">{{ __('User') }}</flux:label>
                    <livewire:components.buttons.invite-user-as-club-member-button :$club :member="$form->member" />
                </flux:field>
            @endif
            <div class="flex gap-2">
                <flux:spacer />

                @if ($isEdit)
                    @if ($member->trashed())
                        <flux:button
                            wire:click="restore"
                            variant="primary"
                            color="green"
                        >
                            {{ __('Restore') }}
                        </flux:button>
                    @else
                        <flux:button
                            wire:click="archive"
                            variant="primary"
                            color="amber"
                        >
                            {{ __('Archive') }}
                        </flux:button>
                    @endif

                    @if (! $this->memberHasCompeted)
                        <flux:button x-on:click="$wire.showDeleteModal = true" variant="danger">{{ __('Delete') }}</flux:button>

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
                    @endif
                @endif

                @if ($isEdit)
                    <flux:button type="submit" variant="primary" :loading="false">{{ __('Update') }}</flux:button>
                @else
                    <flux:button type="submit" variant="primary" :loading="false">{{ __('Create') }}</flux:button>
                @endif
            </div>
        </form>
    </x-containers.club-admin-form>
</div>