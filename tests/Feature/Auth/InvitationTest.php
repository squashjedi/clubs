<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use App\Models\Invitation;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('logged in user can accept an invitation email for a member club', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create();
    $member = Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $invitation = Invitation::factory()->create([
        'member_id' => $member->id,
        'code' => 'test-invitation-code',
    ]);

    expect($member->user_id)->toBeNull();

    $this->actingAs($user);

    $response = $this->get(route('invitations.show', ['invitation' => $invitation->code]));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('message', "You can now submit results for John Doe in {$club->name}.");

    $member->refresh();
    expect($member->user_id)->toBe($user->id);
    $this->assertDatabaseMissing('invitations', [
        'id' => $invitation->id,
    ]);
});

test('guest is redirected to login when accepting an invitation', function () {
    $club = Club::factory()->create();
    $member = Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'deleted_at' => now(),
    ]);
    $invitation = Invitation::factory()->create([
        'member_id' => $member->id,
        'code' => 'test-invitation-code',
    ]);

    $response = $this->get(route('invitations.show', ['invitation' => $invitation->code]));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('invitation', [
        'code' => $invitation->code,
        'message' => "Please login or register to accept the invitation to submit results for Jane Smith in {$club->name}.",
    ]);
});