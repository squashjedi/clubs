<?php

use Flux\Flux;
use App\Models\User;
use Livewire\Component;
use App\Traits\Authentication;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rules;
use App\Enums\PlayerRelationship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

new #[Layout('layouts.auth')] class extends Component {
    use Authentication;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z \'-]+$/u'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z \'-]+$/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['first_name'] = format_name($validated['first_name']);
        $validated['last_name']  = format_name($validated['last_name']);
        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        $player = $user->players()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $this->email,
        ]);

        Auth::login($user);

        $this->checkSessionHasInvitation();

        Flux::toast(
            variant: 'success',
            text: 'Hi ' . auth()->user()->first_name . ', welcome!',
        );

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Create an account" description="Enter your details below to create your account" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    @if (session()->has('invitation'))
        <flux:callout variant="warning" icon="exclamation-circle" :heading="session('invitation.message')" />
    @endif

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- First name -->
        <flux:input
            wire:model="first_name"
            :label="__('First Name')"
            type="text"
            required
            autofocus
            autocomplete="first_name"
            placeholder="First Name"
        />

        <!-- Last name -->
        <flux:input
            wire:model="last_name"
            :label="__('Last Name')"
            type="text"
            required
            autocomplete="first_name"
            placeholder="Last Name"
        />

        <!-- Email Address -->
        <flux:input
            wire:model="email"
            id="email"
            :label="__('Email address')"
            type="email"
            name="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />

        <div class="grid grid-cols-2 gap-4">
            <!-- Password -->
            <flux:input
                wire:model="password"
                id="password"
                :label="__('Password')"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Password"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                wire:model="password_confirmation"
                id="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
                viewable
            />
        </div>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Already have an account?
        <flux:link :href="route('login')" wire:navigate>Log in</flux:link>
    </div>
</div>
