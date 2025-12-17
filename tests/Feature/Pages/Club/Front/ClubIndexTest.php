<?php

use App\Models\Club;

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Club::factory()->create(['name' => 'Giantswood']);
});

test('user can visit the club homepage', function () {
    $this->actingAs($this->user);

    $this->get('giantswood')
        ->assertOk()
        ->assertSeeVolt('pages.club.front.index');
});

test('guest gets redirected to login page', function () {
    $this->get('giantswood')
        ->assertRedirect(route('login'));
});