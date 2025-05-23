<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function sports(): array
{
    return [
        'squash' => Sport::factory()->create(['name' => 'Squash']),
        'badminton' => Sport::factory()->create(['name' => 'Badminton']),
        'tennis' => Sport::factory()->create(['name' => 'Tennis']),
    ];
}

function newParams($overrides = [])
{
    return array_merge([
        'name' => 'New Name',
        'slug' => 'new-slug',
        'sports' => collect([
            sports()['squash']->id,
            sports()['tennis']->id,
        ]),
        'timezone' => 'Timezone/New',
    ], $overrides);
}

test('club admin can visit the club admin profile page', function () {
    $clubAdmin = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $clubAdmin->id, 'name' => 'Giantswood']);

    $this->actingAs($clubAdmin);

    $this->get('giantswood/admin/profile')
        ->assertOk()
        ->assertSeeVolt('clubs.admin.profile.edit')
        ->assertSeeVolt('__components.forms.club-form');
});


test('club admin can update the club profile', function () {
    $clubAdmin = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $clubAdmin->id]);

    $this->actingAs($clubAdmin);

    $component = Volt::test('__components.forms.club-form', ['is_edit' => true])
        ->set('form.club', $club)
        ->set('form.name', 'Primrose')
        ->set('form.sports', [
            sports()['squash']->id,
            sports()['tennis']->id,
        ])
        ->set('form.timezone', 'Europe/London')
        ->call('save')
        ->assertRedirectToRoute('clubs.admin.profile', ['club' => Club::where('slug', 'primrose')->first()]);

    $club = $club->fresh();

    expect($club->name, 'Primrose');
    expect($club->sports[0]->id, sports()['squash']->id);
    expect($club->sports[1]->id, sports()['badminton']->id);
    expect($club->timezone, 'Europe/London');
});


test('name is required', function () {
    $clubAdmin = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $clubAdmin->id, 'name' => 'Giantswood']);

    $this->actingAs($clubAdmin);

    $component = Volt::test('__components.forms.club-form', ['is_edit' => true])
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});


test('timezone is required', function () {
    $clubAdmin = User::factory()->create();
    $club = Club::factory()->create(['user_id' => $clubAdmin->id, 'name' => 'Giantswood']);

    $this->actingAs($clubAdmin);

    $component = Volt::test('__components.forms.club-form', ['is_edit' => true])
        ->set('form.timezone', '')
        ->call('save')
        ->assertHasErrors('form.timezone');
});


test('user cannot visit the club admin profile page', function () {
    $user = User::factory()->create();
    $club = Club::factory()->create(['name' => 'Giantswood']);

    $this->actingAs($user);

    $this->get('giantswood/admin/profile')
        ->assertForbidden();
});


test('guest redirected to login', function () {
    $club = Club::factory()->create(['name' => 'Giantswood']);

    $this->get('giantswood/admin/profile')
        ->assertRedirectToRoute('login');
});