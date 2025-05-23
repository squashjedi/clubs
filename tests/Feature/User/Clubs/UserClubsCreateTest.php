<?php

use Tests\TestCase;
use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->sports = [
        'squash' => Sport::factory()->create(['name' => 'Squash']),
        'badminton' => Sport::factory()->create(['name' => 'Badminton']),
        'tennis' => Sport::factory()->create(['name' => 'Tennis']),
    ];
});

function validParams($overrides = []): array
{
    return array_merge([
        'name' => 'Giantswood Squash Club',
        'slug' => 'giantswood-squash-club',
        'sports' => [
            $this->sports['squash']->id,
            $this->sports['tennis']->id,
        ],
        'timezone' => 'Europe/London',
    ], $overrides);
}

test('user can visit the page', function () {
    $this->actingAs($this->user);

    $response = $this->get('your/clubs/create');

    $response
        ->assertOk()
        ->assertSeeVolt('user.clubs.create')
        ->assertSeeVolt('__components.forms.club-form');
});

test('user can create a new club', function () {
    expect(Club::count(), 0);

    $this->actingAs($this->user);

    $component = Volt::test('__components.forms.club-form')
        ->set('form.name', 'Giantswood')
        ->set('form.sports', [
            $this->sports['squash']->id,
            $this->sports['tennis']->id,
        ])
        ->set('form.timezone', 'Europe/London')
        ->call('save');

    $club = Club::first();

    $component->assertRedirectToRoute('clubs.admin', [$club]);

    expect(Club::count())->toBe(1);
    expect($club->name)->toBe('Giantswood');
    expect($club->sports[0]->id)->toBe($this->sports['squash']->id);
    expect($club->sports[1]->id)->toBe($this->sports['tennis']->id);
    expect($club->timezone)->toBe('Europe/London');
    expect($club->user_id)->toBe($this->user->id);
});

test('name is required', function () {
    $this->actingAs($this->user);

    $component = Volt::test('__components.forms.club-form')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});

test('name must be unique', function () {
    $club = Club::factory()->create(['name' => 'Giantswood']);

    $this->actingAs($this->user);

    $component = Volt::test('__components.forms.club-form')
        ->set('form.name', 'Giantswood')
        ->call('save');

    $component->assertHasErrors('form.name');
});

test('timezone is required', function () {
    $this->actingAs($this->user);

    $component = Volt::test('__components.forms.club-form')
        ->set('form.timezone', '')
        ->call('save');

    $component->assertHasErrors('form.timezone');
});

test('guest gets redirected to login page', function () {
    $response = $this->get('your/clubs/create');

    $response->assertRedirect(route('login'));
});