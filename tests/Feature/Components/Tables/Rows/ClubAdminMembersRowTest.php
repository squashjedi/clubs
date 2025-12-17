<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\Contestant;
use App\Models\Invitation;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->member = Member::factory()->create(['club_id' => $this->club->id]);
    $this->invitation = Invitation::factory()->create(['member_id' => $this->member->id]);
});

test('club admin can permanently delete a member', function () {
    Volt::test('components.tables.rows.club-admin-members-row', ['club' => $this->club, 'member' => $this->member])
        ->call('delete');

    expect($this->member->fresh())->toBeNull();
    expect($this->invitation->fresh())->toBeNull();
});

test('club admin cannot permanently delete a member that has contested', function () {
    Contestant::factory()->create(['member_id' => $this->member->id, 'deleted_at' => now()]);

    Volt::test('components.tables.rows.club-admin-members-row', ['club' => $this->club, 'member' => $this->member])
        ->call('delete')
        ->assertForbidden();

    expect($this->member->fresh())->not->toBeNull();
});