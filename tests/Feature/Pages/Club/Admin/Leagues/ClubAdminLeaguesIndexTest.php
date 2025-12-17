<?php

use App\Models\Club;
use App\Models\User;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
});

test('club admin can visit the club admin leagues page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get('giantswood/admin/leagues')
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.leagues.index')
        ->assertSeeVolt('components.tables.club-admin-leagues-table');
});

test('user cannot visit the club admin leagues page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('giantswood/admin/leagues')
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get('giantswood/admin/leagues')
        ->assertRedirectToRoute('login');
});