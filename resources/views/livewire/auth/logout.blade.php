<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public function logout()
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        Flux::toast(
            variant: 'success',
            duration: 10000,
            heading: 'Logged out',
            text: strip_tags(\Illuminate\Foundation\Inspiring::quote()),
        );

        $this->redirectRoute('home', navigate: true);
    }
}; ?>

<form wire:submit="logout" class="w-full">
    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
        {{ __('Log Out') }}
    </flux:menu.item>
</form>
