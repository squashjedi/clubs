<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use Livewire\Volt\Volt;
use App\Models\ClubSport;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->sports = [
        'squash' => Sport::factory()->create(['id' => 1, 'name' => 'Squash']),
        'tennis' => Sport::factory()->create(['id' => 2, 'name' => 'Tennis']),
    ];
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood']);
    ClubSport::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['squash']->id]);
    ClubSport::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['tennis']->id]);
    $this->league = League::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['squash']->id, 'name' => 'Old Name']);
});

test('club admin can visit the edit a league page', function () {
    $this->actingAs($this->clubAdmin);

    $response = $this->get("giantswood/admin/leagues/{$this->league->id}/edit");

    $response
        ->assertOk()
        ->assertSeeVolt('clubs.admin.leagues.edit');
});

test('club admin can edit a league', function () {
    $this->actingAs($this->clubAdmin);

    $response = Volt::test('clubs.admin.leagues.edit', ['club' => $this->club, 'league' => $this->league])
        ->set('name', 'New League')
        ->set('sport_id', $this->sports['tennis']->id)
        ->call('save');

    $league = $this->club->leagues()->first();

    expect($league->name)->toEqual('New League');
    expect($league->sport_id)->toEqual($this->sports['tennis']->id);
    $response->assertRedirectToRoute('clubs.admin.leagues.edit', [ $this->club, $league ]);
});

test('name is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('clubs.admin.leagues.edit', ['club' => $this->club, 'league' => $this->league])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});

test('sport_id is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('clubs.admin.leagues.edit', ['club' => $this->club, 'league' => $this->league])
        ->set('sport_id', '')
        ->call('save')
        ->assertHasErrors('sport_id');
});

test('user cannot visit the club admin edit a league page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get("giantswood/admin/leagues/{$this->league->id}/edit")
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get("giantswood/admin/leagues/{$this->league->id}/edit")
        ->assertRedirectToRoute('login');
});