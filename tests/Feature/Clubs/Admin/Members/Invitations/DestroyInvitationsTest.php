<?php

use Carbon\Carbon;
use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\Invitation;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood-squash-club']);
    $this->member = Member::factory()->create(['club_id' => $this->club->id, 'user_id' => null, 'deleted_at' => Carbon::now()->subWeeks(2)]);
    $this->invitation = Invitation::factory()->create(['member_id' => $this->member->id]);
});

test('club admin can delete an invitation', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('__components.buttons.invite-member', ['club' => $this->club, 'member' => $this->member])
        ->call('deleteInvitation');

    expect($this->member->invitations)->tobeNull();
});