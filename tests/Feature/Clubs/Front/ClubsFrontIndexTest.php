<?php

use App\Models\Club;
use App\Models\User;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can visit the club homepage', function () {
    $user = User::factory()->create();
    Club::factory()->create(['name' => 'Giantswood']);

    $response = $this->actingAs($user)->get('giantswood');

    $response->assertStatus(200);
});

test('component exists on the page', function () {
    $user = User::factory()->create();
    Club::factory()->create(['name' => 'Giantswood']);

    $response = $this->actingAs($user)->get('giantswood');

    $response->assertSeeVolt('clubs.front.index');
});

test('guest gets redirected to login page', function () {
    Club::factory()->create(['name' => 'Giantswood']);

    $response = $this->get('giantswood');

    $response->assertRedirect(route('login'));
});