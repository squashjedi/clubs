<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\Contestant;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->member = Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Old First Name', 'last_name' => 'Old Last Name']);
});

test('club admin can visit the edit member page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/members/{$this->member->id}/edit")
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.members.edit')
        ->assertSeeVolt('components.forms.member-form');
});

test('club admin can visit the edit a member page of a trashed member', function () {
    $this->member->delete();

    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/members/{$this->member->id}/edit")
        ->assertOk();
});

test('club admin can update a member', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.member-form', ['club' => $this->club, 'member' => $this->member, 'isEdit' => true])
        ->set('form.first_name', 'New First Name')
        ->set('form.last_name', 'New Last Name')
        ->set('form.tel_no', '07999 999 999')
        ->call('save');

    $member = $this->member->fresh();
    expect($member->first_name)->toBe('New First Name');
    expect($member->last_name)->toBe('New Last Name');
    expect($member->tel_no)->toBe('07999 999 999');
});

test('club admin can archive a member', function () {
    expect($this->member->deleted_at)->toBeNull();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.member-form', ['club' => $this->club, 'member' => $this->member, 'is_edit' => true])
        ->call('archive');

    $member = $this->member->fresh();
    expect($member->deleted_at)->not->toBeNull();
});

test('club admin can permanently delete a member if a member has not competed', function () {
    expect($this->member->deleted_at)->toBeNull();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.member-form', ['club' => $this->club, 'member' => $this->member, 'is_edit' => true])
        ->call('delete')
        ->assertRedirectToRoute('club.admin.members', ['club' => $this->club]);

    $member = $this->member->fresh();
    expect($member)->toBeNull();
});

test('club admin cannot permanently delete a member if a member has competed', function () {
    Contestant::factory()->create(['member_id' => $this->member->id]);

    expect($this->member->deleted_at)->toBeNull();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.member-form', ['club' => $this->club, 'member' => $this->member, 'is_edit' => true])
        ->call('delete')
        ->assertForbidden();

    $member = $this->member->fresh();
    expect($member)->not->toBeNull();
});

test('club admin can restore a trashed member', function () {
    $this->member->delete();
    expect($this->member->deleted_at)->not->toBeNull();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.member-form', ['club' => $this->club, 'member' => $this->member, 'is_edit' => true])
        ->call('restore');

    $member = $this->member->fresh();
    expect($member->deleted_at)->toBeNull();
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