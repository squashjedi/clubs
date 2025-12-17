<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Member;
use App\Models\Player;
use App\Traits\Helpers;
use Livewire\Component;
use App\Models\Invitation;
use Livewire\Attributes\On;
use App\Facades\InvitationCode;
use Livewire\Attributes\Validate;
use App\Jobs\SendInvitationMessage;

new class extends Component {
    use Helpers;

    public Club $club;

    public Member $member;

    public bool $hasUser;

    public Invitation $invitation;

    public bool $isTableView = false;

    #[Validate('required', as: 'Email')]
    #[Validate('email', message: 'Must be a valid email address.')]
    public string $email;

    public bool $showErr = true;

    public function sendInvitation()
    {
        abort_if($this->member->isAssigned(), 403);

        $this->showErr = true;
        $this->validate();

        $invitation = $this->member->invitation()->updateOrCreate([
            'member_id' => $this->member->id,
        ], [
            'email' => $this->email,
            'code' => InvitationCode::generate(),
        ]);

        SendInvitationMessage::dispatch($invitation, $this->club, $this->member);

        Flux::toast(
            variant: "success",
            text: "Invitation sent for {$this->member->name}."
        );

        $this->reset('email');

        Flux::modal("invite-{$this->member->id}")->close();
    }

    public function resendInvitation()
    {
        abort_if($this->member->isAssigned(), 403);

        $invitation = $this->member->invitation;
        abort_if(! $invitation, 403);
        $invitation->touch();

        SendInvitationMessage::dispatch($invitation, $this->club, $this->member);

        Flux::toast(
            variant: "success",
            text: "Invitation resent for {$this->member->name}."
        );

        Flux::modal("resend-invitation-{$this->member->id}")->close();
    }

    public function deleteInvitation()
    {
        $this->member->invitation()->delete();

        Flux::toast(
            variant: "success",
            text: "Invitation removed for {$this->member->name}."
        );
    }

    public function removeUser()
    {
        $this->dispatch('user-removed', memberId: $this->member->id);

        DB::transaction(function () {
            $this->member->unassign();

            $originalPlayer = $this->member->player;

            $newPlayer = Player::create([
                'first_name' => $originalPlayer->first_name,
                'last_name' => $originalPlayer->last_name,
                'gender' => $originalPlayer->gender,
                'email' => $originalPlayer->email,
                'tel_no' => $originalPlayer->tel_no,
            ]);

            $this->member->update([
                'player_id' => $newPlayer->id,
            ]);

            Flux::toast(
                variant: "success",
                text: "User detached from {$this->member->name}."
            );
        });
    }
}; ?>

<div>
    @if ($hasUser)
        @if ($isTableView)
            <flux:icon.user variant="mini" class="size-5 text-green-600" />
        @endif
    @else
        @if (! $member->invitation)
            <!-- Invite Button -->
            <flux:modal.trigger name="invite-{{ $member->id }}">
                <flux:button icon="envelope" variant="primary" color="blue">Invite</flux:button>
            </flux:modal.trigger>

            @teleport('body')
                <flux:modal name="invite-{{ $member->id }}" x-on:close="$js.closeModal" class="modal">
                    <form wire:submit="sendInvitation">
                        <x-modals.content>
                            <x-slot:heading>{{ __('Send') }} {{ __('Invitation') }}</x-slot:heading>
                            <flux:text>Invitation for {{ $member->player->name }} or a guardian of {{ $member->player->name }} to take ownership of this member.</flux:text>
                            <flux:field>
                                <flux:label>Email</flux:label>
                                <flux:input wire:model="email" />
                                <flux:error x-show="$wire.showErr" name="email" />
                            </flux:field>
                            <x-slot:buttons>
                                <flux:button type="submit" variant="primary" color="blue">Send</flux:button>
                            </x-slot:buttons>
                        </x-modals.content>
                    </form>
                </flux:modal>
            @endteleport
        @else
            <!-- Invitation -->
            <flux:badge color="amber" icon="envelope">
                <div class="text-xs">
                    <div class="font-semibold">{{ $member->invitation->email }}</div>
                    <div class="">@ {{ $this->datetimeForHumans($member->invitation->updated_at->setTimezone($club->timezone)) }}</div>
                </div>

                <!-- Resend Invitation -->
                <flux:modal.trigger name="resend-invitation-{{ $member->id }}">
                    <flux:badge.close icon="arrow-path" />
                </flux:modal.trigger>

                @teleport('body')
                    <flux:modal name="resend-invitation-{{ $member->id }}" class="modal">
                        <form wire:submit="resendInvitation">
                            <x-modals.content>
                                <x-slot:heading>{{ __('Resend') }} {{ __('Invitation') }}</x-slot:heading>
                                Are you sure you wish to resend the invitation to <span class="font-semibold">{{ $member->invitation->email }}</span> for the ownership of <span class="font-semibold">{{ $member->name }}</span>?
                                <x-slot:buttons>
                                    <flux:button type="submit" variant="primary">Resend</flux:button>
                                </x-slot:buttons>
                            </x-modals.content>
                        </form>
                    </flux:modal>
                @endteleport

                <!-- Delete Invitation -->
                <flux:modal.trigger name="delete-invitation-{{ $member->id }}">
                    <flux:badge.close />
                </flux:modal.trigger>

                @teleport('body')
                    <flux:modal name="delete-invitation-{{ $member->id }}" class="modal">
                        <form wire:submit="deleteInvitation">
                            <x-modals.content>
                                <x-slot:heading>{{ __('Remove') }} {{ __('Invitation') }}</x-slot:heading>
                                Are you sure you wish to remove the invitation sent to <span class="font-semibold">{{ $member->invitation->email }}</span> for the ownership of <span class="font-semibold">{{ $member->name }}</span>?
                                <x-slot:buttons>
                                    <flux:button type="submit" variant="danger">Remove</flux:button>
                                </x-slot:buttons>
                            </x-modals.content>
                        </form>
                    </flux:modal>
                @endteleport
            </flux:badge>
        @endif
    @endif
</div>

@script
<script>
    $js('closeModal', () => {
        $wire.invitation_email = ''
        $wire.showErr = false
        els = document.querySelectorAll("input[name='email']")
        els.forEach(el => el.classList.remove('border-red-500'))
    })
</script>
@endscript