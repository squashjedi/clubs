<?php

use App\Models\Club;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
});

test('club admin can visit the club admin leagues page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get('giantswood/admin/leagues')
        ->assertOk()
        ->assertSeeVolt('clubs.admin.leagues.index');
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