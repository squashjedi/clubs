<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use Livewire\Volt\Volt;
use App\Models\ClubSport;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood']);
    $this->sports = [
        'squash' => Sport::factory()->create(['id' => 1, 'name' => 'Squash']),
        'tennis' => Sport::factory()->create(['id' => 2, 'name' => 'Tennis']),
    ];
    ClubSport::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['squash']->id]);
});

test('club admin can visit the create a league page', function () {
    $this->actingAs($this->clubAdmin);

    $response = $this->get('giantswood/admin/leagues/create');

    $response
        ->assertOk()
        ->assertSeeVolt('clubs.admin.leagues.create');
});

test('club admin can create a new league', function () {
    $this->actingAs($this->clubAdmin);

    $response = Volt::test('clubs.admin.leagues.create', ['club' => $this->club])
        ->set('name', 'Squash League')
        ->set('sport_id', 1)
        ->call('save');

    $league = $this->club->leagues()->first();

    expect($league->name)->toEqual('Squash League');
    expect($league->sport_id)->toEqual(1);
    $response->assertRedirectToRoute('clubs.admin.leagues.edit', ['club' => $this->club, 'league' => $league]);
});

test('name is required', function () {
    $this->actingAs($this->clubAdmin);

    $response = Volt::test('clubs.admin.leagues.create', ['club' => $this->club])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});

test('sport is required', function () {
    $this->actingAs($this->clubAdmin);

    $response = Volt::test('clubs.admin.leagues.create', ['club' => $this->club])
        ->set('sport_id', '')
        ->call('save')
        ->assertHasErrors('sport_id');
});

test('user cannot visit the club admin create a league page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('giantswood/admin/leagues/create')
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get('giantswood/admin/leagues/create')
        ->assertRedirectToRoute('login');
});