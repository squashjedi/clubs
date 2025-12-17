<?php

use App\Models\Club;
use App\Models\User;
use App\Models\League;
use App\Models\Session;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->league = League::factory()->create(['club_id' => $this->club->id]);
    $this->session = Session::factory()->create(['league_id' => $this->league->id]);
});

test('club admin can visit a league session page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/{$this->session->id}")
        ->assertRedirectToRoute('club.admin.leagues.sessions.competitors', ['club' => $this->club, 'league' => $this->league, 'session' => $this->session]);
});

test('user cannot visit a club admin league session page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/{$this->session->id}")
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions/{$this->session->id}")
        ->assertRedirectToRoute('login');
});