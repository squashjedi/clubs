<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\Invitation;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('new users can register when they have an invitation', function () {
    $club = Club::factory()->create(['name' => 'Giantswood']);
    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => null,
        'first_name' => 'Roger',
        'last_name' => 'Federer',
        'deleted_at' => now(),
    ]);
    $invitation = Invitation::factory()->create([
        'member_id' => $member->id,
        'code' => 'INVITATIONCODE123'
    ]);

    $this->withSession(['invitation' => [
        'code' => 'INVITATIONCODE123',
        'message' => 'Please login or register to accept the invitation.',
    ]]);

    $this->assertDatabaseCount('users', 1); // user of the $club

    $response = Volt::test('auth.register')
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseCount('users', 2);

    $user = User::orderBy('id', 'desc')->first();

    expect(session('invitation'))->toBeNull();
    expect($invitation->fresh())->toBeNull();
    expect($member->fresh()->user_id)->toBe($user->id);

    $this->assertAuthenticated();
});