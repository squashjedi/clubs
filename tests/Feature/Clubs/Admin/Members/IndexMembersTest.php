<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
});

test('club admin can visit the club admin members page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get('giantswood/admin/members')
        ->assertOk()
        ->assertSeeVolt('clubs.admin.members.index');
});

test('user cannot visit the club admin members page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('giantswood/admin/members')
        ->assertForbidden();
});


test('guest redirected to login', function () {
    $this->get('giantswood/admin/members')
        ->assertRedirectToRoute('login');
});