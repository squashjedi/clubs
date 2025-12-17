<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\Invitation;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('user can authenticate when they have an invitation', function () {
    $user = User::factory()->create();
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

    $this->actingAs($user);

    $response = Volt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    expect(session('invitation'))->toBeNull();
    expect($invitation->fresh())->toBeNull();
    expect($member->fresh()->user_id)->toBe($user->id);

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = Volt::test('auth.logout')
        ->call('logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});