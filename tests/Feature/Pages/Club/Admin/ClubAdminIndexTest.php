<?php

use App\Models\Club;
use App\Models\User;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
});

test('club admin can visit the admin club dashboard', function () {
    $this->actingAs($this->clubAdmin);

    $this->get('giantswood/admin')
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.index');
});

test('user cannot visit the club admin dashboard if they are not the club admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('giantswood/admin')
        ->assertForbidden();
});


test('guest redirected to login', function () {
    $this->get('giantswood/admin')
        ->assertRedirectToRoute('login');
});