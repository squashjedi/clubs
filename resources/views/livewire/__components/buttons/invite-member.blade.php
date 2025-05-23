<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Member;
use App\Traits\Helpers;
use App\Models\Invitation;
use Livewire\Volt\Component;
use App\Facades\InvitationCode;
use Livewire\Attributes\Validate;
use App\Jobs\SendInvitationMessage;

new class extends Component {
    use Helpers;

    public Club $club;

    public Member $member;

    public Invitation $invitation;

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
            text: "Invitation sent for {$this->member->full_name}."
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
            text: "Invitation resent for {$this->member->full_name}."
        );

        Flux::modal("resend-invitation-{$this->member->id}")->close();
    }

    public function deleteInvitation()
    {
        $this->member->invitation()->delete();

        Flux::toast(
            variant: "success",
            text: "Invitation removed for {$this->member->full_name}."
        );
    }

    public function removeUser()
    {
        $this->member->update([
            'user_id' => null,
        ]);

        Flux::toast(
            variant: "success",
            text: "User removed from {$this->member->full_name}."
        );
    }
}; ?>

<div>
    @if ($member->user_id)
        <flux:badge color="green" icon="user">
            <div>
                <div class="font-semibold">{{ $member->user->name }}</div>
            </div>
            <flux:modal.trigger name="remove-user-{{ $member->id }}">
                <flux:badge.close />
            </flux:modal.trigger>

            @teleport('body')
                <flux:modal name="remove-user-{{ $member->id }}">
                    <form wire:submit="removeUser" class="space-y-6">
                        <flux:heading size="lg">Remove User</flux:heading>
                        <flux:text>Are you sure you wish to remove the privilege of the User <span class="font-semibold">{{ $member->user->name }}</span> to submit results for <span class="font-semibold">{{ $member->full_name }}</span>?</flux:text>

                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary">Remove</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endteleport
        </flux:badge>
    @else
        @if (! $member->invitation)
            <flux:modal.trigger name="invite-{{ $member->id }}">
                <flux:button size="sm" icon="envelope" variant="primary">Invite</flux:button>
            </flux:modal.trigger>

            @teleport('body')
                <flux:modal name="invite-{{ $member->id }}" x-on:close="$js.closeModal">
                    <form wire:submit="sendInvitation" class="space-y-6">
                        <flux:heading size="lg">Send Invitation</flux:heading>
                        <flux:text>Invitation for the privilege of being able to submit results for <span class="font-bold">{{ $member->full_name }}</span>.</flux:text>
                        <flux:field>
                            <flux:label>Email</flux:label>
                            <flux:input wire:model="email" />
                            <flux:error x-show="$wire.showErr" name="email" />
                        </flux:field>
                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary">Send</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endteleport
        @else
            <flux:badge color="amber" icon="envelope">
                <div class="text-xs">
                    <div class="font-semibold">{{ $member->invitation->email }}</div>
                    <div class="">@ {{ $this->dateForHumans($member->invitation->updated_at->setTimezone($club->timezone)) }}</div>
                </div>
                <flux:modal.trigger name="resend-invitation-{{ $member->id }}">
                    <flux:badge.close icon="arrow-path" />
                </flux:modal.trigger>

                @teleport('body')
                    <flux:modal name="resend-invitation-{{ $member->id }}">
                        <form wire:submit="resendInvitation" class="space-y-6">
                            <flux:heading size="lg">Resend Invitation</flux:heading>
                            <flux:text>Are you sure you wish to resend the invitation to <span class="font-semibold">{{ $member->invitation->email }}</span> for the privilege of submitting results for <span class="font-semibold">{{ $member->full_name }}</span>?</flux:text>

                            <div class="flex gap-2">
                                <flux:spacer />
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary">Resend</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endteleport

                <flux:modal.trigger name="delete-invitation-{{ $member->id }}">
                    <flux:badge.close />
                </flux:modal.trigger>

                @teleport('body')
                    <flux:modal name="delete-invitation-{{ $member->id }}">
                        <form wire:submit="deleteInvitation" class="space-y-6">
                            <flux:heading size="lg">Remove Invitation</flux:heading>
                            <flux:text>Are you sure you wish to remove the invitation sent to <span class="font-semibold">{{ $member->invitation->email }}</span> for the privilege of submitting results for <span class="font-semibold">{{ $member->full_name }}</span>?</flux:text>

                            <div class="flex gap-2">
                                <flux:spacer />
                                <flux:modal.close>
                                    <flux:button variant="ghost">Cancel</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="danger">Remove</flux:button>
                            </div>
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