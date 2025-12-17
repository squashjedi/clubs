<?php

use App\Models\Club;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can visit the page', function () {
    $this->actingAs($this->user);

    $this->get('your/clubs/create')
        ->assertOk()
        ->assertSeeVolt('pages.your.clubs.create');
});

test('user can create a new club', function () {
    expect(Club::count(), 0);

    $this->actingAs($this->user);

    Volt::test('components.forms.club-form')
        ->set('form.name', 'Giantswood')
        ->set('form.timezone', 'Europe/London')
        ->call('save')
        ->assertRedirectToRoute('club.admin', ['club' => Club::where('slug', 'giantswood')->first()]);

    $club = Club::first();

    expect(Club::count())->toBe(1);
    expect($club->name)->toBe('Giantswood');
    expect($club->timezone)->toBe('Europe/London');
    expect($club->user_id)->toBe($this->user->id);
});

test('name is required', function () {
    $this->actingAs($this->user);

    Volt::test('components.forms.club-form')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});

test('name must be unique', function () {
    Club::factory()->create(['name' => 'Giantswood']);

    $this->actingAs($this->user);

    Volt::test('components.forms.club-form')
        ->set('form.name', 'Giantswood')
        ->call('save')
        ->assertHasErrors('form.name');
});

test('timezone is required', function () {
    $this->actingAs($this->user);

    $component = Volt::test('components.forms.club-form')
        ->set('form.timezone', '')
        ->call('save')
        ->assertHasErrors('form.timezone');
});

test('guest gets redirected to login page', function () {
    $this->get('your/clubs/create')
        ->assertRedirect(route('login'));
});