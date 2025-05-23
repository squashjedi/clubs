<?php

use App\Models\Club;
use App\Models\User;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);


test('component exists on the page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('your/clubs/administrate');

    $response->assertSeeVolt('user.clubs.administrate.index');
});


test('user can visit the page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('your/clubs/administrate');

    $response->assertStatus(200);
});


test("displays the clubs that the user administrates in alphabetical order", function () {
    $user = User::factory()->create();
    Club::factory()->create(['user_id' => $user->id, 'name' => 'Primrose']);
    Club::factory()->create(['name' => 'Warrington']);
    Club::factory()->create(['user_id' => $user->id, 'name' => 'Giantswood']);

    $this->actingAs($user);

    $response = Volt::test('user.clubs.administrate.index');
    
    $response
        ->assertViewHas('clubs', function ($clubs) {
            return count($clubs) === 2;
        })
        ->assertSeeInOrder(['Giantswood', 'Primrose']);
});


test("displays no clubs if the user is not a member of any club", function () {
    $user = User::factory()->create();

    Club::factory()->create();

    $this->actingAs($user);

    Volt::test('user.clubs.administrate.index')
        ->assertViewHas('clubs', function ($clubs) {
            return count($clubs) === 0;
        })
        ->assertSeeHtml("You don't administrate any club.");
});


test('guest gets redirected to login page', function () {
    $response = $this->get('your/clubs/administrate');

    $response->assertRedirect(route('login'));   
});