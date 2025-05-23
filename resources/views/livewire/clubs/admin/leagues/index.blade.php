<?php

use App\Models\Club;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Database\Eloquent\Builder;

new #[Layout('components.layouts.club.admin.app')] class extends Component
{
    use WithPagination;

    public Club $club;

    public $search = '';

    #[Url]
    public $sortBy = 'id';

    #[Url]
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->authorize('view', $this->club);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    protected function applySorting($query)
    {
        if ($this->sortBy === 'id') {
            $query->orderBy('id', $this->sortDirection);
        } elseif ($this->sortBy === 'name') {
            $query->orderBy('name', $this->sortDirection);
        }

        return $query;
    }

    protected function applySearch($query)
    {
        return $this->search === ''
            ? $query
            : $query->where(function (Builder $query) {
                $query->where('id', $this->search)
                    ->orWhere('name', 'like', "%{$this->search}%");
            });
    }

    public function with(): array
    {
        $query = $this->club->leagues()->with('sport');
        $query = $this->applySearch($query);
        $query = $this->applySorting($query);

        return [
            'league_count' => $this->club->leagues()->count(),
            'leagues' => $query->orderBy('name')->paginate(20),
        ];
    }
}; ?>


<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Leagues") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    <div class="flex justify-between">
        <x-ui.typography.h3>{{ __("Leagues") }}</x-ui.typography.h3>
        <flux:button :href="route('clubs.admin.leagues.create', [$club])" icon="plus" variant="primary" wire:navigate>{{ __("League") }}</flux:button>
    </div>

    @if ($league_count === 0)
        <div>There are no leagues yet.</div>
    @else
        <flux:input wire:model.live.debounce.500ms="search" icon="magnifying-glass" placeholder="Search # or league" class="max-w-md" />

        @if ($leagues->total() > 0)
            <div class="relative">
                <flux:table :paginate="$leagues">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')" class="w-4">#</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">League</flux:table.column>
                        <flux:table.column align="end"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($leagues as $league)
                            <flux:table.row>
                                <flux:table.cell>{{ $league->id }}</flux:table.cell>
                                <flux:table.cell>{{ $league->name }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button :href="route('clubs.admin.leagues.sessions', [$club, $league])" icon="pencil" size="xs" wire:navigate></flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div wire:loading class="absolute inset-0 bg-white opacity-50"></div>
                <div wire:loading.flex class="flex items-center justify-center absolute inset-0">
                    <flux:icon.loading class="size-10 text-gray-500" />
                </div>
            </div>
        @else
            <div>Sorry, no results found for '{{ $search }}'.</div>
        @endif
    @endif
</div>