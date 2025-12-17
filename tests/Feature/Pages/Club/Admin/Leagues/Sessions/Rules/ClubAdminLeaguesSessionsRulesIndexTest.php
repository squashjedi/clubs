<?php

use Carbon\Carbon;
use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\Session;
use Livewire\Volt\Volt;
use App\Models\TallyUnit;

beforeEach(function () {
    Carbon::setTestNow();

    $this->startingAt = now()->addMonths(1);
    $this->endingAt = now()->addMonths(2);

    $this->clubAdmin = User::factory()->create();
    $this->sports = [
        'squash' => Sport::factory()->create(['name' => 'Squash']),
    ];
    $this->tallyUnits = [
        'games' => TallyUnit::factory()->create(['id' => 1, 'sport_id' => $this->sports['squash'], 'name' => 'Game', 'max_best_of' => 5]),
        'points' => TallyUnit::factory()->create(['id' => 2, 'sport_id' => $this->sports['squash'], 'name' => 'Point', 'max_best_of' => 30]),
    ];
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->league = League::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['squash']->id]);
    $this->session = Session::factory()->create(['league_id' => $this->league->id, 'tally_unit_id' => $this->tallyUnits['games']->id, 'best_of' => 1, 'pts_win' => 0, 'pts_draw' => 0, 'pts_per_set' => 0, 'pts_play' => 0]);
});

test('club admin can visit the league session rules page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/{$this->session->id}/rules")
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.leagues.sessions.rules.index')
        ->assertSeeVolt('components.forms.session-rules-form');
});

test('club admin can edit the league session rules', function () {
    Volt::test('components.forms.session-form', ['club' => $this->club, 'league' => $this->league, 'session' => $this->session, 'isEdit' => true])
        ->set('form.tally_unit_id', 1)
        ->set('form.best_of', 5)
        ->set('form.pts_win', 3)
        ->set('form.pts_draw', 1)
        ->set('form.pts_per_set', 1)
        ->set('form.pts_play', 1)
        ->call('save');

    $session = Session::first();

    expect($session->tally_unit_id)->toBe(1);
    expect($session->best_of)->toBe(5);
    expect($session->pts_win)->toBe(3);
    expect($session->pts_draw)->toBe(1);
    expect($session->pts_per_set)->toBe(1);
    expect($session->pts_play)->toBe(1);
});

test('Active Period is required', function () {
    Volt::test('components.forms.session-form', ['club' => $this->club, 'league' => $this->league, 'session' => $this->session, 'isEdit' => true])
        ->set('form.active_period', '')
        ->call('save')
        ->assertHasErrors('form.active_period');
});

test('user is forbidden to visit the edit league session page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/{$this->session->id}/edit")
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/{$this->session->id}/edit")
        ->assertRedirectToRoute('login');
});