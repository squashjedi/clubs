<?php

use Carbon\Carbon;
use App\Models\Club;
use App\Models\User;
use App\Models\League;
use App\Models\Session;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('club admin can visit a club admin league session page', function () {
    $clubAdmin = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $clubAdmin->id, 'name' => 'Giantswood']);
    $league = League::factory()->create(['club_id' => $club->id]);
    $session = Session::factory()->create(['league_id' => $league->id]);

    $this->actingAs($clubAdmin);

    $this->get("giantswood/admin/leagues/{$league->id}/sessions/{$session->id}")
        ->assertOk()
        ->assertSeeVolt('clubs.admin.leagues.sessions.show');
});

test('user cannot visit a club admin league session page', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create(['name' => 'Giantswood']);
    $league = League::factory()->create(['club_id' => $club->id]);
    $session = Session::factory()->create(['league_id' => $league->id]);

    $this->actingAs($user);

    $this->get("giantswood/admin/leagues/{$league->id}/sessions/{$session->id}")
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $club = Club::factory()->create(['name' => 'Giantswood']);
    $league = League::factory()->create(['club_id' => $club->id]);
    $session = Session::factory()->create(['league_id' => $league->id]);

    $this->get("giantswood/admin/leagues/{$league->id}/sessions/{$session->id}")
        ->assertRedirectToRoute('login');
});