<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureProfileIsComplete;

// Auth::login(User::find(1));

Route::get('/', function () {
    return view('welcome');
})->name('home');

require __DIR__.'/auth.php';

Route::get('invitations/{invitation:code}', [App\Http\Controllers\InvitationsController::class, 'show'])->name('invitations.show')->scopeBindings();

Route::livewire('testing', 'pages::testing');

Route::middleware(['auth', EnsureProfileIsComplete::class])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::livewire('settings/profile', 'pages::settings.profile')->name('settings.profile');
    Route::livewire('settings/players', 'pages::settings.players.index')->name('settings.players');
    Route::livewire('settings/players/create', 'pages::settings.players.create')->name('settings.players.create');
    Route::livewire('settings/players/{player}', 'pages::settings.players.edit')->name('settings.players.edit');
    Route::livewire('settings/password', 'pages::settings.password')->name('settings.password');
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('settings.appearance');

    Route::middleware(['verified'])->group(function () {
        Route::get('dashboard', function () {
            session()->reflash();
            return redirect()->route('your.clubs.follow');
        })->name('dashboard');

        Route::livewire('your/clubs/follow', 'pages::your.clubs.follow.index')->name('your.clubs.follow');
        Route::livewire('your/clubs/administrate', 'pages::your.clubs.administrate.index')->name('your.clubs.administrate');
        Route::livewire('your/clubs/create', 'pages::your.clubs.create')->name('your.clubs.create');

        Route::livewire('{club}', 'pages::club.front.index')->name('club');

        Route::middleware('can:view,club')->group(function () {
            Route::livewire('{club}/players/{player}/invitations/{invitation:code}', 'pages::club.front.players.invitations.show')->name('club.players.invitations.show')->withTrashed()->scopeBindings();

            Route::livewire('{club}/admin', 'pages::club.admin.index')->name('club.admin');
            Route::livewire('{club}/admin/profile', 'pages::club.admin.profile.index')->name('club.admin.profile');
            Route::livewire('{club}/admin/players', 'pages::club.admin.players.index')->name('club.admin.players');
            Route::livewire('{club}/admin/players/create', 'pages::club.admin.players.create')->name('club.admin.players.create');
            Route::livewire('{club}/admin/players/{player}/edit', 'pages::club.admin.players.edit')->name('club.admin.players.edit')->withTrashed()->scopeBindings();
            Route::livewire('{club}/admin/leagues', 'pages::club.admin.leagues.index')->name('club.admin.leagues');
            Route::livewire('{club}/admin/leagues/create', 'pages::club.admin.leagues.create')->name('club.admin.leagues.create');
            Route::livewire('{club}/admin/leagues/{league}/edit', 'pages::club.admin.leagues.edit')->name('club.admin.leagues.edit')->withTrashed()->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions', 'pages::club.admin.leagues.sessions.index')->name('club.admin.leagues.sessions')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/create', 'pages::club.admin.leagues.sessions.create')->name('club.admin.leagues.sessions.create')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/{session}', 'pages::club.admin.leagues.sessions.show')->name('club.admin.leagues.sessions.show')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/{session}/entrants', 'pages::club.admin.leagues.sessions.entrants.index')->name('club.admin.leagues.sessions.entrants')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/{session}/structure', 'pages::club.admin.leagues.sessions.structure.index')->name('club.admin.leagues.sessions.structure')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/{session}/competitors', 'pages::club.admin.leagues.sessions.competitors.index')->name('club.admin.leagues.sessions.competitors')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/{session}/tables', 'pages::club.admin.leagues.sessions.tables.index')->name('club.admin.leagues.sessions.tables')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/{session}/tables/tiers/{tier}/divisions/{division}', 'pages::club.admin.leagues.sessions.tables.division.table')->name('club.admin.leagues.sessions.tables.division.table')->scopeBindings();
            Route::livewire('{club}/admin/leagues/{league}/sessions/{session}/tables/tiers/{tier}/divisions/{division}/results', 'pages::club.admin.leagues.sessions.tables.division.matrix')->name('club.admin.leagues.sessions.tables.division.matrix')->scopeBindings();
        });
    });
});




