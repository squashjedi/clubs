<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->member = Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Old First Name', 'last_name' => 'Old Last Name']);
});

test('club admin can visit the club admin edit member page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/members/{$this->member->id}/edit")
        ->assertOk()
        ->assertSeeVolt('clubs.admin.members.edit');
});


test('club admin can update a member', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('clubs.admin.members.edit', ['club' => $this->club, 'member' => $this->member])
        ->set('form.first_name', 'New First Name')
        ->set('form.last_name', 'New Last Name')
        ->call('save');

    $member = $this->member->fresh();
    expect($member->first_name)->toBe('New First Name');
    expect($member->last_name)->toBe('New Last Name');
});


test('user cannot view the edit club member page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get("giantswood/admin/members/{$this->member->id}/edit")
        ->assertForbidden();
});


test('guest redirected to login', function () {
    $this->get("giantswood/admin/members/{$this->member->id}/edit")
        ->assertRedirectToRoute('login');
});