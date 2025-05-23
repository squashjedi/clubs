<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Sport;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.club.admin.app')] class extends Component {
    public Club $club;

    #[Validate('required', as: 'league name')]
    public string $name;

    #[Validate('required', as: 'Sport')]
    public int $sport_id;

    public function mount()
    {
        $this->authorize('create', $this->club);
    }

    public function save()
    {
        $this->validate();

        $league = $this->club->leagues()->create([
            'name' => $this->name,
            'sport_id' => $this->sport_id,
        ]);

        Flux::toast(
            variant: 'success',
            text: 'League created'
        );

        $this->redirectRoute('clubs.admin.leagues.edit', [$this->club, $league], navigate: true);
    }

    public function with(): array
    {
        return [
            'sports' => Sport::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues', [$club])" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Create") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <div class="flex justify-between">
        <x-ui.typography.h3>{{ __("Create New League") }}</x-ui.typography.h3>
    </div>
    <form wire:submit="save" class="space-y-6 max-w-lg">
        <flux:input wire:model="name" label="League name" class="max-w-sm" required />

        <flux:select wire:model="sport_id" variant="listbox" label="Sport" placeholder="Select sport..." class="max-w-sm">
            @foreach ($sports as $sport)
                <flux:select.option value="{{ $sport->id }}">{{ $sport->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</div>