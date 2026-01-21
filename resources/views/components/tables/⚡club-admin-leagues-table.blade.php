<?php

use App\Models\Club;
use Livewire\Component;
use App\Enums\ModelStatus;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public Club $club;

    #[Url]
    public $searchId = '';

    #[Url]
    public $searchLeague = '';

    #[Url]
    public $searchSport = '';

    #[Url]
    public ModelStatus $searchLeagueStatus = ModelStatus::Active;

    #[Url]
    public $sortBy = 'club_league_id';

    #[Url]
    public $sortDirection = 'desc';

    #[On('delete')]
    public function delete() {}

    public function resetList()
    {
        $this->reset('searchId', 'searchSport', 'searchLeague', 'sortBy', 'sortDirection');
    }

    public function updatedSearchId()
    {
        $this->resetPage();
    }

    public function updatedSearchLeague()
    {
        $this->resetPage();
    }

    public function updatedSearchSport()
    {
        $this->resetPage();
    }

    public function updatedSearchLeagueStatus()
    {
        $this->resetList();
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
        return $query
            ->when($this->sortBy === 'club_league_id',
                fn ($query) => $query->orderBy('club_league_id', $this->sortDirection)
            )
            ->when($this->sortBy === 'sport',
                fn ($query) => $query->orderBy('sports_name', $this->sortDirection)
            )
            ->when($this->sortBy === 'name',
                fn ($query) => $query->orderBy('name', $this->sortDirection)
            );
    }

    protected function applySearchLeagueStatus($query)
    {
        return $query
            ->when($this->searchLeagueStatus === ModelStatus::Trashed,
                function ($query) {
                    return $query->onlyTrashed();
                })
                ->when($this->searchLeagueStatus === ModelStatus::All,
                function ($query) {
                    return $query->withTrashed();
                });
    }

    protected function applySearchId($query)
    {
        return $query
            ->when($this->searchId !== '',
                fn ($query) => $query->where('club_league_id', $this->searchId)
            );
    }

    protected function applySearchSport($query)
    {
        return $query
            ->when($this->searchSport !== '',
                fn ($query) => $query->where('sports.name', 'LIKE', '%'.$this->searchSport.'%')
            );
    }

    protected function applySearchLeague($query)
    {
        return $query
            ->when($this->searchLeague !== '',
                fn ($query) => $query->where('leagues.name', 'LIKE', '%'.$this->searchLeague.'%')
            );
    }

    #[Computed]
    public function sports()
    {
        return $this->club->leagueSports();
    }

    #[Computed]
    public function league_active_count()
    {
        return $this->club->leagues()->count();
    }

    #[Computed]
    public function league_trashed_count()
    {
        return $this->club->leagues()->onlyTrashed()->count();
    }

    #[Computed]
    public function league_all_count()
    {
        return $this->club->leagues()->withTrashed()->count();
    }

    #[Computed]
    public function leagues()
    {
        $query = $this->club->leagues()->with('latestSession')
            ->join('sports', 'leagues.sport_id', '=', 'sports.id')
            ->select('leagues.*', 'sports.name as sports_name');
        $query = $this->applySearchLeagueStatus($query);
        $query = $this->applySearchId($query);
        $query = $this->applySearchSport($query);
        $query = $this->applySearchLeague($query);
        $query = $this->applySorting($query);

        return $query->orderBy('name')->paginate(20);
    }
}; ?>

<x-ui.cards.mobile>
    @if ($this->league_all_count === 0)
        <flux:text>There are no leagues yet.</flux:text>
    @else
        <flux:radio.group
            wire:model.live="searchLeagueStatus"
            variant="cards"
            class="flex gap-4"
        >
            <x-radios.club-admin-table-radio
                value="{{ ModelStatus::Active }}"
                heading="Active"
                count="{{ $this->league_active_count }}"
            />
            <x-radios.club-admin-table-radio
                value="{{ ModelStatus::Trashed }}"
                heading="Archived"
                count="{{ $this->league_trashed_count }}"
            />
            <x-radios.club-admin-table-radio
                value="{{ ModelStatus::All }}"
                heading="All"
                count="{{ $this->league_all_count }}"
            />
        </flux:radio.group>

        <div class="relative">
            <flux:table :paginate="$this->leagues">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'club_league_id'" :direction="$sortDirection" wire:click="sort('club_league_id')" class="w-20">#</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'sport'" :direction="$sortDirection" wire:click="sort('sport')" class="">Sport</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">League</flux:table.column>
                    <flux:table.column align="end"></flux:table.column>
                </flux:table.columns>
                <flux:table.columns>
                    <flux:table.column>
                        <flux:input wire:model.live.debounce.500ms="searchId" :loading="false" />
                    </flux:table.column>
                    <flux:table.column>
                        <flux:select wire:model.live="searchSport" variant="listbox">
                            <flux:select.option value="">All</flux:select.option>
                            @foreach ($this->sports as $sport)
                                <flux:select.option value="{{ $sport->name }}" wire:key="{{ $sport->id }}">{{ $sport->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.column>
                    <flux:table.column>
                        <flux:input wire:model.live.debounce.500ms="searchLeague" :loading="false" />
                    </flux:table.column>
                    <flux:table.column class="w-0">
                        <flux:tooltip content="Reset">
                            <flux:button wire:click="resetList" icon="list-restart"  />
                        </flux:tooltip>
                    </flux:table.column>
                </flux:table.columns>

                @if ($this->leagues->total() > 0)
                    <flux:table.rows>
                        @foreach ($this->leagues as $league)
                            <livewire:tables.rows.club-admin-leagues-row :$club :$league :key="$league->id" />
                        @endforeach
                    </flux:table.rows>
                @endif

            </flux:table>

            @if ($this->leagues->total() === 0)
                <x-tables.items-not-found colspan="4" collection_name="{{ $searchLeagueStatus->label() }} leagues" />
            @endif

            <div wire:loading class="absolute inset-0 bg-white opacity-50" />
        </div>
    @endif
</x-ui.cards.mobile>
