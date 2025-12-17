<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->member = Member::factory()->create(['club_id' => $this->club->id]);
});

test('club admin can visit the club admin members page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get('giantswood/admin/members')
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.members.index')
        ->assertSeeVolt('components.tables.club-admin-members-table');
});

test('user cannot visit the club admin members page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('giantswood/admin/members')
        ->assertForbidden();
});


test('guest redirected to login', function () {
    $this->get('giantswood/admin/members')
        ->assertRedirectToRoute('login');
});