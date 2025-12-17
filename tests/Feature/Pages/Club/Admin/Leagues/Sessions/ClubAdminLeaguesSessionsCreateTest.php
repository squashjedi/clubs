<?php

use Carbon\Carbon;
use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\Member;
use App\Models\Session;
use Livewire\Volt\Volt;
use App\Models\TallyUnit;
use App\Models\Competitor;

beforeEach(function () {
    Carbon::setTestNow();
    $this->clubAdmin = User::factory()->create();
    $this->sports = [
        'squash' => Sport::factory()->create(['name' => 'Squash']),
    ];
    $this->tallyUnits = [
        'game' => TallyUnit::factory()->create(['id' => 1, 'sport_id' => $this->sports['squash'], 'name' => 'Game', 'max_best_of' => 5]),
        'point' => TallyUnit::factory()->create(['id' => 2, 'sport_id' => $this->sports['squash'], 'name' => 'Point', 'max_best_of' => 30]),
    ];
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'slug' => 'giantswood']);
    $this->league = League::factory()->create(['club_id' => $this->club->id, 'sport_id' => $this->sports['squash']->id]);
});

test('club admin can visit a create a new league session page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/create")
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.leagues.sessions.create');
});

test('club admin can create a new league session when there have been no previous league sessions', function () {
    $starting_at = now();
    $ending_at = now()->addMonths(1);

    Volt::test('pages.club.admin.leagues.sessions.create', ['club' => $this->club, 'league' => $this->league])
        ->set('activePeriod', [
            'start' => $starting_at,
            'end' => $ending_at,
        ])
        ->call('save')
        ->assertRedirectToRoute('club.admin.leagues.sessions.competitors', ['club' => $this->club, 'league' => $this->league, 'session' => Session::first()]);

        $session = Session::first();

        expect($session->starting_at->startOfDay()->toDateTimeString())->toBe($starting_at->startOfDay()->toDateTimeString());
        expect($session->ending_at->endOfDay()->toDateTimeString())->toBe($ending_at->endOfDay()->toDateTimeString());
        expect($session->competitors()->before()->count())->tobe(0);
});

test('club admin can create a new league session when there is a previous league session', function () {
    $previousSession = Session::factory()->create(['league_id' => $this->league->id]);
    $members = [
        'federer' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Roger', 'last_name' => 'Federer']),
        'nadal' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Rafa', 'last_name' => 'Nadal']),
        'murray' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Andy', 'last_name' => 'Murray']),
    ];
    $previousSessionCompetitorsAfter = collect([
        Competitor::factory()->create(['league_session_id' => $previousSession->id, 'is_after' => 1, 'member_id' => $members['federer']->id, 'index' => 0]),
        Competitor::factory()->create(['league_session_id' => $previousSession->id, 'is_after' => 1, 'member_id' => $members['nadal']->id, 'index' => 1]),
        Competitor::factory()->create(['league_session_id' => $previousSession->id, 'is_after' => 1, 'member_id' => $members['murray']->id, 'index' => 2]),
    ]);

    $starting_at = now();
    $ending_at = now()->addMonths(1);

    Volt::test('pages.club.admin.leagues.sessions.create', ['club' => $this->club, 'league' => $this->league])
        ->set('activePeriod', [
            'start' => $starting_at,
            'end' => $ending_at,
        ])
        ->call('save')
        ->assertRedirectToRoute('club.admin.leagues.sessions.competitors', ['club' => $this->club, 'league' => $this->league, 'session' => $this->league->latestSession]);

        $session = $this->league->latestSession;
        $sessionCompetitorsBefore = $session->competitors()->before()->get();

        expect($session->starting_at->startOfDay()->toDateTimeString())->toBe($starting_at->startOfDay()->toDateTimeString());
        expect($session->ending_at->endOfDay()->toDateTimeString())->toBe($ending_at->endOfDay()->toDateTimeString());
        expect($session->competitors()->before()->count())->tobe(3);
        expect($sessionCompetitorsBefore->count())->toBe($previousSessionCompetitorsAfter->count());
});

test('Active Period is required', function () {
    Volt::test('pages.club.admin.leagues.sessions.create', ['club' => $this->club, 'league' => $this->league])
        ->set('activePeriod', '')
        ->call('save')
        ->assertHasErrors('activePeriod');
});

test('user cannot visit a create a new league session page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/create")
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/create")
        ->assertRedirectToRoute('login');
});