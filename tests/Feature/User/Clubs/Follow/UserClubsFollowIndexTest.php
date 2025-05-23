<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\ClubUser;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can visit the page', function () {
    $this->actingAs($this->user);

    $response = $this->get('your/clubs/follow');

    $response
        ->assertOk()
        ->assertSeeVolt('user.clubs.follow.index');
});

test("displays no clubs if the user is not following any club", function () {
    Club::factory()->create(['name' => 'Northern']);

    $this->actingAs($this->user);

    Volt::test('user.clubs.follow.index')
        ->assertViewHas('clubs', function ($clubs) {
            return count($clubs) === 0;
        })
        ->assertSeeHtml("You don't follow any club.");
});


test('guest gets redirected to login page', function () {
    $response = $this->get('your/clubs/follow');

    $response->assertRedirect(route('login'));
});