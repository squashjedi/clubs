<?php

use Carbon\Carbon;
use App\Models\User;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('profile page is displayed', function () {
    $this->actingAs($this->user);

    $this->get('/settings/profile')->assertOk();
});

test('profile information can be updated', function () {
    $this->actingAs($this->user);

    $response = Volt::test('settings.profile')
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user = $this->user->refresh();

    expect($user->name)->toEqual('John Doe');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $this->actingAs($this->user);

    $response = Volt::test('settings.profile')
        ->set('name', 'John Doe')
        ->set('email', $this->user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($this->user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $this->actingAs($this->user);

    $response = Volt::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($this->user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $this->actingAs($this->user);

    $response = Volt::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($this->user->fresh())->not->toBeNull();
});