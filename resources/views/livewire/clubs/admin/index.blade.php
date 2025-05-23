<?php

use App\Models\Club;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.club.admin.app')] class extends Component
{
    public Club $club;

    public function mount()
    {
        $this->authorize('view', $this->club);
    }
}; ?>


<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item>Dashboard</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <div>Welcome to the {{ $club->name }} dashboard.</div>
</div>
