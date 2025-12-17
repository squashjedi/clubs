<?php

use App\Models\Club;
use App\Enums\Gender;
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
    public string $searchApplicant = '';

    #[Url]
    public string $searchGender = '';

    #[Url]
    public $sortBy = 'name';

    #[Url]
    public $sortDirection = 'desc';

    #[On('delete')]
    public function delete() {}

    public function resetList()
    {
        $this->reset('searchApplicant', 'searchGender', 'sortBy', 'sortDirection');
    }

    public function updatedSearchMember()
    {
        $this->resetPage();
    }

    public function updatedSearchGender()
    {
        $this->resetPage();
    }

    public function updatedSearchUser()
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

    /**
     * Apply sorting – by club_member_id or by PLAYER name.
     */
    protected function applySorting($query)
    {
        return $query
            ->when($this->sortBy === 'club_member_id',
                function ($query) {
                    return $query->orderBy('members.club_member_id', $this->sortDirection);
                })
            ->when($this->sortBy === 'name',
                function ($query) {
                    // Join players so we can sort by player last_name, first_name
                    return $query
                        ->leftJoin('players', 'players.id', '=', 'members.player_id')
                        ->select('members.*') // keep Member as the model
                        ->orderBy('players.last_name', $this->sortDirection)
                        ->orderBy('players.first_name', $this->sortDirection);
                });
    }

    /**
     * Search by PLAYER full name (first_name + last_name).
     */
    protected function applySearchMember($query)
    {
        return $query
            ->when($this->searchApplicant !== '',
                function ($query) {
                    return $query->whereHas('player', function ($q) {
                        $q->whereRaw("CONCAT(players.first_name, ' ', players.last_name) LIKE ?", ['%'.$this->searchApplicant.'%']);
                    });
                });
    }

    /**
     * Search by PLAYER full name (first_name + last_name).
     */
    protected function applySearchGender($query)
    {
        return $query
            ->when($this->searchGender !== '',
                function ($query) {
                    return $query->whereHas('player', function ($q) {
                        $q->where('players.gender', $this->searchGender);
                    });
                });
    }

    #[Computed]
    public function member_active_count()
    {
        return $this->club->members()->count();
    }

    #[Computed]
    public function member_all_count()
    {
        return $this->club->members()->withTrashed()->count();
    }

    #[Computed]
    public function member_trashed_count()
    {
        return $this->club->members()->onlyTrashed()->count();
    }

    #[Computed]
    public function members()
    {
        // Start from club members, eager load player + users + contestants
        $query = $this->club
            ->members()
            ->with(['player.users'])
            ->withCount('contestants');

        $query = $this->applySearchMember($query);
        $query = $this->applySearchGender($query);
        $query = $this->applySorting($query);

        // Also eager-load invitation; sorting already handled above
        return $query
            ->with('invitation')
            ->paginate(20);
    }
}; ?>


<div class="space-y-6">
    @if ($this->member_all_count === 0)
        <flux:text>There are no members yet.</flux:text>
    @else
        <div class="relative">
            <flux:table :paginate="$this->members">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Member</flux:table.column>
                    <flux:table.column>Gender</flux:table.column>
                    <flux:table.column class="w-0"></flux:table.column>
                </flux:table.columns>

                <flux:table.columns>
                    <flux:table.column>
                        <flux:input wire:model.live.debounce.500ms="searchApplicant" :loading="false" />
                    </flux:table.column>
                    <flux:table.column>
                        <flux:select wire:model.live="searchGender" variant="listbox">
                            <flux:select.option value="">All</flux:select.option>
                            @foreach (Gender::cases() as $gender)
                                <flux:select.option value="{{ $gender->value }}">{{ $gender->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.column>
                    <flux:table.column class="w-0">
                        <flux:tooltip content="Reset">
                            <flux:button wire:click="resetList" icon="list-restart"  />
                        </flux:tooltip>
                    </flux:table.column>
                </flux:table.columns>

                @if ($this->members->total() > 0)
                    <flux:table.rows>
                        @foreach ($this->members as $member)
                            <livewire:tables.rows.club-admin-applicants-row :$club :$member :player="$member->player" :key="$member->id" />
                        @endforeach
                    </flux:table.rows>
                @endif
            </flux:table>

            @if ($this->members->total() === 0)
                <x-tables.items-not-found colspan="3" collectionName="{{ $searchApplicantStatus->label() }} members" />
            @endif

            <div wire:loading class="absolute inset-0 bg-white opacity-50" />
        </div>
    @endif
</div>