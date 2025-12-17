<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('dashboard')
        ->assertRedirect(route('your.clubs.follow'));
});

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect(route('login'));
});