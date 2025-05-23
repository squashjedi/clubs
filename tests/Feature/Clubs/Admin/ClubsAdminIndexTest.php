<?php

use App\Models\Club;
use App\Models\User;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);


test('component exists on the page', function () {
    $clubAdmin = User::factory()->create();
    Club::factory()->create(['user_id' => $clubAdmin->id, 'name' => 'Giantswood']);

    $response = $this->actingAs($clubAdmin)->get('giantswood/admin');

    $response->assertSeeVolt('clubs.admin.index');
});



test('club admin can visit the admin club dashboard', function () {
    $clubAdmin = User::factory()->create();
    Club::factory()->create(['user_id' => $clubAdmin->id, 'name' => 'Giantswood']);

    $response = $this->actingAs($clubAdmin)->get('giantswood/admin');

    $response->assertStatus(200);
});


test('user cannot visit the club admin dashboard if they are not the club admin', function () {
    $user = User::factory()->create();

    Club::factory()->create(['name' => 'Giantswood']);

    $response = $this->actingAs($user)->get('giantswood/admin');

    $response->assertForbidden();   
});


test('guest redirected to login', function () {
    Club::factory()->create(['name' => 'Giantswood']);

    $response = $this->get('giantswood/admin');

    $response->assertRedirectToRoute('login');
});