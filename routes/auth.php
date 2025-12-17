<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VerifyEmailController;

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')
        ->name('login');

    Route::livewire('register', 'pages::auth.register')
        ->name('register');

    Route::livewire('forgot-password', 'pages::auth.forgot-password')
        ->name('password.request');

    Route::livewire('reset-password/{token}', 'pages::auth.reset-password')
        ->name('password.reset');

});

Route::middleware('auth')->group(function () {
    Route::livewire('verify-email', 'pages::auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::livewire('confirm-password', 'pages::auth.confirm-password')
        ->name('password.confirm');
});

Route::livewire('logout', 'livewire:auth.logout')
    ->name('logout');
