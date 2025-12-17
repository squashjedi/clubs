<?php

use App\Models\Club;
use App\Models\User;
use App\Models\League;
use App\Models\Session;

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
    $this->league = League::factory()->create(['club_id' => $this->club->id]);
});

test('club admin can visit the club admin league sessions page if there are league sessions', function () {
    Session::factory()->create(['league_id' => $this->league->id]);

    $this->actingAs($this->clubAdmin);

    $this->get("giantswood/admin/leagues/{$this->league->id}/sessions")
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.leagues.sessions.index');
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