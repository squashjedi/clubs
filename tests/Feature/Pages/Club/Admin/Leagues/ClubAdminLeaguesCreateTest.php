<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use Livewire\Volt\Volt;
use App\Models\ClubSport;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood']);
    $this->sports = collect([
        'squash' => Sport::factory()->create(['id' => 1, 'name' => 'Squash']),
        'tennis' => Sport::factory()->create(['id' => 2, 'name' => 'Tennis']),
    ]);
    ClubSport::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['squash']->id]);
});

test('club admin can visit the create a league page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get('giantswood/admin/leagues/create')
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.leagues.create')
        ->assertSeeVolt('components.forms.league-form');
});

test('club admin can create a new league', function () {
    League::factory()->create(['club_id' => $this->club->id, 'club_league_id' => 2, 'deleted_at' => now()]);

    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.league-form', ['club' => $this->club, 'sports' => $this->sports])
        ->set('form.name', 'Squash League')
        ->set('form.sport_id', 1)
        ->call('save')
        ->assertRedirectToRoute('club.admin.leagues.sessions.create', ['club' => $this->club, 'league' => $this->club->leagues()->latest()->first()]);;

    $league = $this->club->leagues()->latest()->first();

    expect($league->club_league_id)->toEqual(3);
    expect($league->name)->toEqual('Squash League');
    expect($league->sport_id)->toEqual(1);
});

test('name is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.league-form', ['club' => $this->club, 'sports' => $this->sports])
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});

test('sport is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.league-form', ['club' => $this->club, 'sports' => $this->sports])
        ->set('form.sport_id', '')
        ->call('save')
        ->assertHasErrors('form.sport_id');
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