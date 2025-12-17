<?php

use App\Models\Club;
use App\Models\User;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can visit the page', function () {
    $this->actingAs($this->user);

    $this->get('your/clubs/administrate')
        ->assertOk()
        ->assertSeeVolt('pages.your.clubs.administrate.index');
});

test("displays the clubs that the user administrates in alphabetical order", function () {
    Club::factory()->create(['user_id' => $this->user->id, 'name' => 'Primrose']);
    Club::factory()->create(['name' => 'Warrington']);
    Club::factory()->create(['user_id' => $this->user->id, 'name' => 'Giantswood']);

    $this->actingAs($this->user);

    Volt::test('pages.your.clubs.administrate.index')
        ->assertViewHas('clubs', function ($clubs) {
            return count($clubs) === 2;
        })
        ->assertSeeInOrder(['Giantswood', 'Primrose']);
});


test("displays no clubs if the user is not a member of any club", function () {
    $this->actingAs($this->user);

    Volt::test('pages.your.clubs.administrate.index')
        ->assertViewHas('clubs', function ($clubs) {
            return count($clubs) === 0;
        })
        ->assertSeeHtml("You don't administrate any club.");
});


test('guest gets redirected to login page', function () {
    $this->get('your/clubs/administrate')
        ->assertRedirect(route('login'));
});