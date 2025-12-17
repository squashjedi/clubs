<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use Livewire\Volt\Volt;
use Illuminate\Database\Eloquent\Model;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->clubAdmin = User::factory()->create();
    $this->club = Club::factory()->create(['user_id' => $this->clubAdmin->id, 'name' => 'Giantswood']);
});

test('club admin can visit the club admin profile page', function () {
    $this->actingAs($this->clubAdmin);

    $this->get('giantswood/admin/profile')
        ->assertOk()
        ->assertSeeVolt('pages.club.admin.profile.index')
        ->assertSeeVolt('components.forms.club-form');
});

test('club admin can update the club profile', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.club-form', ['club' => $this->club, 'is_edit' => true])
        ->set('form.name', 'Primrose')
        ->set('form.timezone', 'Europe/London')
        ->call('save')
        ->assertRedirectToRoute('club.admin.profile', ['club' => Club::where('slug', 'primrose')->first()]);

    $club = $this->club->fresh();

    expect($club->name, 'Primrose');
    expect($club->timezone, 'Europe/London');
});

test('name is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.club-form', ['club' => $this->club, 'is_edit' => true])
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});

test('timezone is required', function () {
    $this->actingAs($this->clubAdmin);

    Volt::test('components.forms.club-form', ['club' => $this->club, 'is_edit' => true])
        ->set('form.timezone', '')
        ->call('save')
        ->assertHasErrors('form.timezone');
});

test('user cannot visit the club admin profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('giantswood/admin/profile')
        ->assertForbidden();
});

test('guest redirected to login', function () {
    $club = Club::factory()->create(['name' => 'Giantswood']);

    $this->get('giantswood/admin/profile')
        ->assertRedirectToRoute('login');
});