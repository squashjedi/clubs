<?php

use App\Models\Club;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.club.front.app')] class extends Component {
    public Club $club;
}; ?>

<div class="space-y-6">
    <x-ui.typography.h3>
        Welcome to {{ $club->name }}
    </x-ui.typography.h3>
</div>
