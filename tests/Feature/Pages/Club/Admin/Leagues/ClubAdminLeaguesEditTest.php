<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use Livewire\Volt\Volt;
use App\Models\ClubSport;

beforeEach(function () {
    $this->sports = collect([
        'squash' => Sport::factory()->create(['id' => 1, 'name' => 'Squash']),
        'tennis' => Sport::factory()->create(['id' => 2, 'name' => 'Tennis']),
    ]);
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood']);
    $this->league = League::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['squash']->id, 'club_league_id' => 4, 'name' => 'Old Name']);
});

test('club admin can visit the edit a league page of a trashed league', function () {
    $this->league->delete();

    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/leagues/{$this->league->id}/edit")
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.leagues.edit')
        ->assertSeeVolt('components.forms.league-form');
});

test('club admin can edit a league', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.league-form', ['club' => $this->club, 'league' => $this->league, 'sports' => $this->sports, 'isEdit' => true])
        ->set('form.name', 'New League')
        ->call('save')
        ->assertRedirectToRoute('club.admin.leagues.edit', [ 'club' => $this->club, 'league' => $this->league ]);

    $league = $this->league->fresh();

    expect($league->club_league_id)->toEqual(4);
    expect($league->name)->toEqual('New League');
});

test('club admin can archive a league', function () {
    expect($this->league->deleted_at)->toBeNull();

    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.league-form', ['club' => $this->club, 'league' => $this->league, 'isEdit' => true])
        ->call('archive');

    $league = $this->league->fresh();
    expect($league->deleted_at)->not->toBeNull();
});

test('name is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.league-form', ['club' => $this->club, 'league' => $this->league, 'sports' => $this->sports, 'isEdit' => true])
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});

test('sport_id is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.league-form', ['club' => $this->club, 'league' => $this->league, 'sports' => $this->sports, 'isEdit' => true])
        ->set('form.sport_id', '')
        ->call('save')
        ->assertHasErrors('form.sport_id');
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