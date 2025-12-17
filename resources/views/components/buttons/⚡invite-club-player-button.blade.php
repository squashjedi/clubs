<?php

use Flux\Flux;
use App\Models\Club;
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

    public Player $player;

    public Invitation $invitation;

    public bool $isTableView = false;

    #[Validate('required', as: 'Email')]
    #[Validate('email', message: 'Must be a valid email address.')]
    public string $email;

    public bool $showErr = true;

    public function sendInvitation()
    {
        abort_if($this->player->users_exists, 403);

        $this->showErr = true;
        $this->validate();

        $invitation = $this->player->invitations()->updateOrCreate([
            'club_id' => $this->club->id,
            'player_id' => $this->player->id,
        ], [
            'email' => $this->email,
            'code' => InvitationCode::generate(),
        ]);

        SendInvitationMessage::dispatch($invitation, $this->club, $this->player);

        Flux::toast(
            variant: "success",
            text: "Invitation sent for {$this->player->name}."
        );

        $this->reset('email');

        Flux::modal("invite-{$this->player->id}")->close();
    }

    public function resendInvitation()
    {
        abort_if($this->player->users_exists, 403);

        $invitation = $this->player->invitations()->first();
        abort_if(! $invitation, 403);
        $invitation->touch();

        SendInvitationMessage::dispatch($invitation, $this->club, $this->player);

        Flux::toast(
            variant: "success",
            text: "Invitation resent for {$this->player->name}."
        );

        Flux::modal("resend-invitation-{$this->player->id}")->close();
    }

    public function deleteInvitation()
    {
        $this->player->invitations()->delete();

        Flux::toast(
            variant: "success",
            text: "Invitation removed for {$this->player->name}."
        );
    }

    public function removeUser()
    {
        $this->dispatch('user-removed', memberId: $this->player->id);

        DB::transaction(function () {
            $this->player->unassign();

            $originalPlayer = $this->player;

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
                text: "User detached from {$this->player->name}."
            );
        });
    }
}; ?>

<div>
    @if ($player->users_exists)
        @if ($isTableView)
            <flux:icon.user variant="mini" class="size-5 text-green-600" />
        @endif
    @else
        @if (! $player->invitations()->exists())
            <!-- Invite Button -->
            <flux:modal.trigger name="invite-{{ $player->id }}">
                <flux:button icon="envelope" variant="primary" color="blue">Invite</flux:button>
            </flux:modal.trigger>

            @teleport('body')
                <flux:modal name="invite-{{ $player->id }}" x-on:close="$js.closeModal" class="modal">
                    <form wire:submit="sendInvitation">
                        <x-modals.content>
                            <x-slot:heading>{{ __('Send') }} {{ __('Invitation') }}</x-slot:heading>
                            <flux:text>Invitation for {{ $player->name }} or a guardian of {{ $player->name }} to take ownership of this member.</flux:text>
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
                    <div class="font-semibold">{{ $player->invitations()->first()->email }}</div>
                    <div class="">@ {{ $this->datetimeForHumans($player->invitations()->first()->updated_at->setTimezone($club->timezone)) }}</div>
                </div>

                <!-- Resend Invitation -->
                <flux:modal.trigger name="resend-invitation-{{ $player->id }}">
                    <flux:badge.close icon="arrow-path" />
                </flux:modal.trigger>

                @teleport('body')
                    <flux:modal name="resend-invitation-{{ $player->id }}" class="modal">
                        <form wire:submit="resendInvitation">
                            <x-modals.content>
                                <x-slot:heading>{{ __('Resend') }} {{ __('Invitation') }}</x-slot:heading>
                                Are you sure you wish to resend the invitation to <span class="font-semibold">{{ $player->invitations()->first()->email }}</span> to take ownership?
                                <x-slot:buttons>
                                    <flux:button type="submit" variant="primary">Resend</flux:button>
                                </x-slot:buttons>
                            </x-modals.content>
                        </form>
                    </flux:modal>
                @endteleport

                <!-- Delete Invitation -->
                <flux:modal.trigger name="delete-invitation-{{ $player->id }}">
                    <flux:badge.close />
                </flux:modal.trigger>

                @teleport('body')
                    <flux:modal name="delete-invitation-{{ $player->id }}" class="modal">
                        <form wire:submit="deleteInvitation">
                            <x-modals.content>
                                <x-slot:heading>{{ __('Remove') }} {{ __('Invitation') }}</x-slot:heading>
                                Are you sure you wish to remove the invitation sent to <span class="font-semibold">{{ $player->invitations()->first()->email }}</span> to take ownership of <span class="font-semibold">{{ $player->name }}</span>?
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