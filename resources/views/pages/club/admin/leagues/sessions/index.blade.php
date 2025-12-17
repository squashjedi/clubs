<?php

use App\Models\Club;
use App\Models\League;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component
{
    use WithPagination;

    public Club $club;

    public League $league;

    public function mount()
    {
        if (! $this->league->sessions()->exists()) {
            $this->redirectRoute('club.admin.leagues.sessions.create', ['club' => $this->club, 'league' => $this->league], navigate: true);
        }
    }

    public function with(): array
    {
        $query = $this->league->sessions();
        // $query = $this->applySearch($query);
        // $query = $this->applySorting($query);

        return [
            'session_count' => $this->league->sessions()->count(),
            'sessions' => $query->orderBy('id', 'desc')->paginate(20),
        ];
    }
}; ?>

<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [$club]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.edit', [$club, $league]) }}" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Sessions") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-headings.league-with-sub-heading :$club :$league />

    <div class="flex items-center justify-between">
        <x-headings.page-heading>Sessions</x-headings.page-heading>
        @if (! $league->sessions()->exists() || ! is_null($league->latestSession()->first()?->processed_at))
            <div class="flex flex-col items-end">
                <flux:button href="{{ route('club.admin.leagues.sessions.create', [$club, $league]) }}" variant="primary" icon="plus" wire:navigate>{{ __('New Session') }}</flux:button>
            </div>
        @endif
    </div>

    @if ($session_count === 0)
        <flux:text>There are no sessions yet.</flux:text>
    @else
        <div class="relative">
            <flux:table :paginate="$sessions">
                <flux:table.columns>
                    <flux:table.column>{{ __('Active Period') }}</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end"></flux:table.column>
                </flux:table.columns>

                @if ($sessions->total() > 0)
                    <flux:table.rows>
                        @foreach ($sessions as $session)
                            <livewire:tables.rows.club-admin-sessions-row :key="$session->id" :$club :$league :$session />
                        @endforeach
                    </flux:table.rows>
                @else
                    <x-tables.items-not-found colspan="2" collection_name="sessions" />
                @endif
            </flux:table>

            <div wire:loading class="absolute inset-0 bg-white opacity-50"></div>
        </div>
    @endif
</div>
