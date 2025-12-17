<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()
        ->hasMembers(1, ['club_member_id' => 2])
        ->hasMembers(1, ['club_member_id' => 3, 'deleted_at' => now()])
        ->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
});

test('club admin can visit the club admin create member page', function () {
    $this->withoutExceptionHandling();

    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/members/create")
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.members.create')
        ->assertSeeVolt('components.forms.member-form');
});

test('club admin can create a new member', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.member-form', ['club' => $this->club])
        ->set('form.first_name', 'John')
        ->set('form.last_name', 'Doe')
        ->set('form.tel_no', '07999 999 999')
        ->call('save')
        ->assertRedirectToRoute('club.admin.members', ['club' => $this->club]);

    $member = Member::orderBy('id', 'desc')->first();
    expect($member->club_member_id)->toBe(4);
    expect($member->first_name)->toBe('John');
    expect($member->last_name)->toBe('Doe');
    expect($member->tel_no)->toBe('07999 999 999');
});

test('user cannot view the create a new member page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get("giantswood/admin/members/create")
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get("giantswood/admin/members/create")
        ->assertRedirectToRoute('login');
});