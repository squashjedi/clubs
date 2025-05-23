<?php

use Carbon\Carbon;
use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\Invitation;
use App\Jobs\SendInvitationMessage;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood']);
    $this->member = Member::factory()->create(['club_id' => $this->club->id, 'user_id' => null, 'deleted_at' => Carbon::now()->subWeeks(2)]);
    $this->invitation = Invitation::factory()->create(['member_id' => $this->member->id]);
});

test('club admin can resend an invitation', function () {
    Queue::fake();

    $this->actingAs($this->clubAdmin);

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
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

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
        ->call('resendInvitation')
        ->assertForbidden();
});

test('club admin cannot resend an invitation if it does not exist', function () {
    $this->invitation->delete();

    $this->actingAs($this->clubAdmin);

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
        ->call('resendInvitation')
        ->assertForbidden();
});