<?php

use Flux\Flux;
use Carbon\Carbon;
use App\Models\User;
use App\Enums\Gender;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use App\Enums\PlayerRelationship;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

new #[Layout('layouts.user')] class extends Component {
    public string $name = '';
    public ?string $first_name = '';
    public ?string $last_name = '';
    public Gender $gender;
    public ?Carbon $dob = null;
    public string $email = '';
    public ?string $tel_no = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->first_name = Auth::user()->first_name;
        $this->last_name = Auth::user()->last_name;
        $this->gender = Auth::user()->gender;
        $this->dob = Auth::user()->dob;
        $this->email = Auth::user()->email;
        $this->tel_no = Auth::user()->tel_no;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z \'-]+$/u'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z \'-]+$/u'],
            'gender' => ['required'],
            'dob' => ['nullable'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'tel_no' => ['nullable']
        ]);

        $validated['first_name'] = format_name($validated['first_name']);
        $validated['last_name']  = format_name($validated['last_name']);

        $this->first_name = $validated['first_name'];
        $this->last_name  = $validated['last_name'];

        DB::transaction(function () use ($user, $validated) {

            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            // Update player with all user profile data
            $user->players()->wherePivot('relationship', 'self')->update([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'gender' => $user->gender,
                'dob' => $user->dob,
                'email' => $user->email,
                'tel_no' => $user->tel_no,
            ]);

            // Update all players with user email and tel_no
            $user->players()->wherePivot('relationship', 'guardian')->update([
                'email' => $user->email,
                'tel_no' => $user->tel_no,
            ]);

        });

        Flux::toast(
            variant: 'success',
            text: 'Profile updated.',
        );


    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function playerId()
    {
        return Auth::user()->players()->wherePivot('relationship', PlayerRelationship::Self)->first()->id;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            @if (session('flash'))
                <flux:callout
                    variant="warning"
                    icon="exclamation-triangle"
                >
                    <flux:callout.text>
                        {{ session('flash') }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:heading size="lg">Your Player ID: {{ $this->playerId }}</flux:heading>

            <div class="grid sm:grid-cols-2 gap-6">
                <flux:input wire:model="first_name" :label="__('First Name')" type="text" required autofocus autocomplete="first_name" />
                <flux:input wire:model="last_name" :label="__('Last Name')" type="text" required autocomplete="last_name" />
            </div>

            <flux:radio.group
                wire:model="gender"
                label="Gender"
                variant="cards"
                :indicator="false"
                class="max-w-sm"
            >
                @foreach (collect(Gender::cases())->reject(fn($g) => $g === Gender::Unknown) as $gender)
                    <flux:radio value="{{ $gender->value }}" label="{{ $gender->name }}" class="text-center" />
                @endforeach
            </flux:radio.group>

            <flux:date-picker wire:model="dob" selectable-header clearable label="Date of Birth" class="max-w-xs" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" name="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:input wire:model="tel_no" label="Tel No" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
