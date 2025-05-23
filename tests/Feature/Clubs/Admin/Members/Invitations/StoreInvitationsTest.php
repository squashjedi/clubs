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

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood']);
    $this->member = Member::factory()->create(['club_id' => $this->club->id, 'user_id' => null, 'deleted_at' => Carbon::now()->subWeeks(2)]);
});

test('club admin can send an invitation', function () {
    Queue::fake();

    $invitationCodeGenerator = Mockery::mock(InvitationCodeGenerator::class, [
        'generate' => 'INVITATIONCODE1234',
    ]);
    $this->app->instance(InvitationCodeGenerator::class, $invitationCodeGenerator);

    expect(Invitation::count())->toBe(0);

    $this->actingAs($this->clubAdmin);

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
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
    $this->actingAs($this->clubAdmin);

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
        ->set('email', '')
        ->call('sendInvitation')
        ->assertHasErrors('email');
});

test('email must be an email', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
        ->set('email', 'not-an-email')
        ->call('sendInvitation')
        ->assertHasErrors('email');
});

test('club admin cannot send an invitation to a member that already has a user', function () {
    $otherUser = User::factory()->create();
    $this->member->update([
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->clubAdmin);

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
        ->set('email', 'john@doe.com')
        ->call('sendInvitation')
        ->assertForbidden();
});