<?php

use App\Models\User;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can visit the page', function () {
    $this->actingAs($this->user);

    $this->get('your/clubs/follow')
        ->assertOk()
        ->assertSeeVolt('pages.your.clubs.follow.index');
});

test("displays no clubs if the user is not following any club", function () {
    $this->actingAs($this->user);

    Volt::test('pages.your.clubs.follow.index')
        ->assertViewHas('clubs', function ($clubs) {
            return count($clubs) === 0;
        })
        ->assertSeeHtml("You don't follow any club.");
});


test('guest gets redirected to login page', function () {
    $this->get('your/clubs/follow')
        ->assertRedirect(route('login'));
});