<?php

use App\Models\Club;
use App\Models\User;
use App\Models\League;
use App\Models\Member;
use App\Models\Session;
use Livewire\Volt\Volt;
use App\Models\Competitor;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->league = League::factory()->create(['club_id' => $this->club->id]);
    $this->session = Session::factory()->create(['league_id' => $this->league->id]);
});

test('club admin can visit the club backend league session competitors page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/{$this->session->id}/competitors")
        ->assertOk()
        ->assertSeeVolt('components.generic.session-seedings')
        ->assertSeeVolt('components.generic.session-seedings-previous-final-positions');
});

test('club admin can reset the competitors list to be blank if there is not a previous session', function () {
    $members = [
        'federer' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Roger', 'last_name' => 'Federer']),
        'nadal' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Rafa', 'last_name' => 'Nadal']),
        'murray' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Andy', 'last_name' => 'Murray']),
    ];
    collect([
        Competitor::factory()->create(['league_session_id' => $this->session->id, 'is_after' => 0, 'member_id' => $members['federer']->id, 'index' => 0]),
        Competitor::factory()->create(['league_session_id' => $this->session->id, 'is_after' => 0, 'member_id' => $members['nadal']->id, 'index' => 1]),
        Competitor::factory()->create(['league_session_id' => $this->session->id, 'is_after' => 0, 'member_id' => $members['murray']->id, 'index' => 2]),
    ]);

    expect($this->session->competitors()->before()->count())->toBe(3);

    Volt::test('components.generic.session-seedings', ['club' => $this->club, 'session' => $this->session])
        ->call('restore');

    expect($this->session->competitors()->before()->count())->toBe(0);
});

test('club admin can reset the competitors list if there is a previous session to be previous sessions competitors after list', function () {
    $members = [
        'federer' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Roger', 'last_name' => 'Federer']),
        'nadal' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Rafa', 'last_name' => 'Nadal']),
        'djokovic' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Andy', 'last_name' => 'djokovic']),
        'draper' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Jack', 'last_name' => 'Draper']),
        'alcaraz' => Member::factory()->create(['club_id' => $this->club->id, 'first_name' => 'Carlos', 'last_name' => 'Alcaraz']),
    ];
    $previousSessionCompetitorsAfter = collect([
        Competitor::factory()->create(['league_session_id' => $this->session->id, 'is_after' => 1, 'member_id' => $members['federer']->id, 'index' => 0]),
        Competitor::factory()->create(['league_session_id' => $this->session->id, 'is_after' => 1, 'member_id' => $members['nadal']->id, 'index' => 1]),
        Competitor::factory()->create(['league_session_id' => $this->session->id, 'is_after' => 1, 'member_id' => $members['djokovic']->id, 'index' => 2]),
    ]);

    $session = Session::factory()->create(['league_id' => $this->league->id, 'tally_unit_id' => $this->session->tally_unit_id]);

    $sessionCompetitorsBefore = collect([
        Competitor::factory()->create(['league_session_id' => $session->id, 'is_after' => 0, 'member_id' => $members['alcaraz']->id, 'index' => 0]),
        Competitor::factory()->create(['league_session_id' => $session->id, 'is_after' => 0, 'member_id' => $members['djokovic']->id, 'index' => 1]),
        Competitor::factory()->create(['league_session_id' => $session->id, 'is_after' => 0, 'member_id' => $members['draper']->id, 'index' => 2]),
    ]);

    Volt::test('components.generic.session-seedings', ['club' => $this->club, 'league' => $this->league, 'session' => $session])
        ->call('restore');

    expect($session->competitors()->before()->count())->toBe(3);
});

test('user cannot visit the club admin league sessions page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions")
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions")
        ->assertRedirectToRoute('login');
});