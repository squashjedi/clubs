<?php

use App\Models\Club;
use App\Models\User;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Auth::login(User::find(1));

Route::get('/', function () {
    return view('welcome');
})->name('home');

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::middleware(['verified'])->group(function () {
        Route::redirect('dashboard', 'your/clubs/follow')->name('dashboard');
        // Volt::route('dashboard', 'dashboard')->name('dashboard');
        Volt::route('your/clubs/follow', 'user.clubs.follow.index')->name('user.clubs.follow');
        Volt::route('your/clubs/administrate', 'user.clubs.administrate.index')->name('user.clubs.administrate');
        Volt::route('your/clubs/create', 'user.clubs.create')->name('user.clubs.create');

        Volt::route('{club}', 'clubs.front.index')->name('clubs.front');
        Volt::route('{club}/admin', 'clubs.admin.index')->name('clubs.admin');
        Volt::route('{club}/admin/profile', 'clubs.admin.profile.edit')->name('clubs.admin.profile');
        Volt::route('{club}/admin/members', 'clubs.admin.members.index')->name('clubs.admin.members');
        Volt::route('{club}/admin/members/create', 'clubs.admin.members.create')->name('clubs.admin.members.create')->scopeBindings();
        Volt::route('{club}/admin/members/{member}/edit', 'clubs.admin.members.edit')->name('clubs.admin.members.edit')->scopeBindings();
        Volt::route('{club}/admin/leagues', 'clubs.admin.leagues.index')->name('clubs.admin.leagues');
        Volt::route('{club}/admin/leagues/create', 'clubs.admin.leagues.create')->name('clubs.admin.leagues.create');
        Volt::route('{club}/admin/leagues/{league}/edit', 'clubs.admin.leagues.edit')->name('clubs.admin.leagues.edit')->scopeBindings();
        Volt::route('{club}/admin/leagues/{league}/sessions', 'clubs.admin.leagues.sessions.index')->name('clubs.admin.leagues.sessions')->scopeBindings();
        Volt::route('{club}/admin/leagues/{league}/sessions/{session}', 'clubs.admin.leagues.sessions.show')->name('clubs.admin.leagues.sessions.show')->scopeBindings();
        Volt::route('{club}/admin/leagues/{league}/sessions/{session}/edit', 'clubs.admin.leagues.sessions.edit')->name('clubs.admin.leagues.sessions.edit')->scopeBindings();
    });
});




