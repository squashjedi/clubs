<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use Livewire\Volt\Volt;
use App\Models\ClubUser;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function() {
    $this->user = User::factory()->create();
    $this->club = Club::factory()->create(['name' => 'Giantswood']);
});

test('component exists on the page', function () {
    $this->actingAs($this->user);

    $response = $this->get('giantswood');

    $response->assertSeeVolt('clubs.front.follow');
});

test('user can follow a club', function () {
    expect(ClubUser::count())->toBe(0);

    $this->actingAs($this->user);

    Volt::test('clubs.front.follow', [$this->club])
        ->assertSee('Follow')
        ->call('follow')
        ->assertSee('Unfollow');

    expect(ClubUser::count())->toBe(1);
});

test('user can unfollow a club when they already follow', function () {
    ClubUser::factory()->create(['club_id' => $this->club->id, 'user_id' => $this->user->id]);

    expect(ClubUser::count())->toBe(1);

    $this->actingAs($this->user);

    Volt::test('clubs.front.follow', [$this->club])
        ->assertSee('Unfollow')
        ->call('follow')
        ->assertSee('Follow');

    expect(ClubUser::count())->toBe(0);
});