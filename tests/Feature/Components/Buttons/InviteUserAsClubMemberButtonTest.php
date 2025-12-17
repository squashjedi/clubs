<?php

use Carbon\Carbon;
use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\Invitation;
use App\InvitationCodeGenerator;
use App\Jobs\SendInvitationMessage;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id]);
    $this->member = Member::factory()->create(['club_id' => $this->club->id, 'user_id' => null, 'deleted_at' => Carbon::now()->subWeeks(2)]);
    $this->invitation = Invitation::factory()->create(['member_id' => $this->member->id]);
});

// Send invitation

test('club admin can send an invitation', function () {
    $this->invitation->delete();

    Queue::fake();

    $invitationCodeGenerator = Mockery::mock(InvitationCodeGenerator::class, [
        'generate' => 'INVITATIONCODE1234',
    ]);
    $this->app->instance(InvitationCodeGenerator::class, $invitationCodeGenerator);

    expect(Invitation::count())->toBe(0);

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->set('email', 'john@doe.com')
        ->call('sendInvitation');

    $invitation = Invitation::first();

    Queue::assertPushed(SendInvitationMessage::class, function ($job) use ($invitation) {
        return $job->invitation->is($invitation)
            && $job->club->is($this->club)
            && $job->member->is($this->member);
        });

    expect(Invitation::count())->toBe(1);
    $this->assertDatabaseHas('invitations', ['member_id' => $this->member->id, 'email' => 'john@doe.com', 'code' => 'INVITATIONCODE1234']);
});

test('email is required', function () {
    $this->invitation->delete();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->set('email', '')
        ->call('sendInvitation')
        ->assertHasErrors('email');
});

test('email must be an email', function () {
    $this->invitation->delete();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->set('email', 'not-an-email')
        ->call('sendInvitation')
        ->assertHasErrors('email');
});

test('club admin cannot send an invitation to a member that already has a user', function () {
    $this->invitation->delete();

    $otherUser = User::factory()->create();
    $this->member->update([
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->set('email', 'john@doe.com')
        ->call('sendInvitation')
        ->assertForbidden();
});

// Resend invitation

test('club admin can resend an invitation', function () {
    Queue::fake();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->call('resendInvitation');

    Queue::assertPushed(SendInvitationMessage::class, function ($job) {
        return $job->invitation->is($this->invitation)
            && $job->club->is($this->club)
            && $job->member->is($this->member);
    });
});

test('club admin cannot resend an invitation to a member that already has a user', function () {
    $otherUser = User::factory()->create();
    $this->member->update([
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->call('resendInvitation')
        ->assertForbidden();
});

test('club admin cannot resend an invitation if it does not exist', function () {
    $this->invitation->delete();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->call('resendInvitation')
        ->assertForbidden();
});

// Delete invitation

test('club admin can delete an invitation', function () {
    Invitation::factory()->create(['member_id' => $this->member->id]);

    $this->actingAs($this->clubAdmin);

    Volt::test('components.buttons.invite-user-as-club-member-button', ['club' => $this->club, 'member' => $this->member])
        ->call('deleteInvitation');

    expect($this->member->invitations)->tobeNull();
});