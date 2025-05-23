<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Sport;
use App\Models\League;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.club.admin.app')] class extends Component {
    public Club $club;

    public League $league;

    #[Validate('required')]
    public string $name;

    #[Validate('required')]
    public int $sport_id;

    public function mount()
    {
        $this->authorize('update', $this->league);

        $this->name = $this->league->name;
        $this->sport_id = $this->league->sport_id;
    }

    public function save()
    {
        $this->validate();

        $this->league->update([
            'name' => $this->name,
            'sport_id' => $this->sport_id,
        ]);

        Flux::toast(
            variant: 'success',
            text: 'League updated.'
        );

        $this->redirectRoute('clubs.admin.leagues.edit', ['club' => $this->club, 'league' => $this->league], navigate: true);
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
        <flux:breadcrumbs.item :href="route('clubs.admin.leagues.edit', [$club, $league])" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Edit") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-clubs.admin.league-menu :$club :$league />

    <x-ui.typography.h4>Edit League</x-ui.typography.h4>

    <form wire:submit="save" class="space-y-6 max-w-lg">
        <flux:input wire:model="name" label="Name" class="max-w-sm" required />

        <flux:select wire:model="sport_id" variant="listbox" label="Sport" placeholder="Select sport..." class="max-w-sm">
            @foreach ($sports as $sport)
                <flux:select.option value="{{ $sport->id }}">{{ $sport->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</div>