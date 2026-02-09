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
    public string $searchId = '';

    #[Url]
    public string $searchPlayer = '';

    #[Url]
    public string $searchGender = '';

    #[Url]
    public string $searchUser = '';

    #[Url]
    public ModelStatus $searchPlayerStatus = ModelStatus::Active;

    #[Url]
    public $sortBy = 'club_player_id';

    #[Url]
    public $sortDirection = 'desc';

    public int $perPage = 20;

    #[On('delete')]
    public function delete() {}

    public function resetList()
    {
        $this->reset('searchId', 'searchPlayer', 'searchGender', 'searchUser', 'sortBy', 'sortDirection');
        $this->resetPerPage();
    }

    public function resetPerPage()
    {
        $this->perPage = 20;
    }

    public function updatedSearchId()
    {
        $this->resetPerPage();
        $this->resetPage();
    }

    public function updatedSearchPlayer()
    {
        $this->resetPerPage();
        $this->resetPage();
    }

    public function updatedSearchGender()
    {
        $this->resetPerPage();
        $this->resetPage();
    }

    public function updatedSearchUser()
    {
        $this->resetPerPage();
        $this->resetPage();
    }

    public function updatedSearchPlayerStatus()
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

        $this->resetPerPage();
        $this->resetPage();
    }

    public function loadMore()
    {
        $this->perPage += 20;
    }

    /**
     * Apply sorting – by club_player_id or by PLAYER name.
     */
    protected function applySorting($query)
    {
        return $query
            ->when($this->sortBy === 'club_player_id',
                function ($query) {
                    return $query->orderBy('club_player.club_player_id', $this->sortDirection);
                })
            ->when($this->sortBy === 'name',
                function ($query) {
                    // Join players so we can sort by player last_name, first_name
                    return $query
                        ->orderBy('last_name', $this->sortDirection)
                        ->orderBy('first_name', $this->sortDirection);
                });
    }

    protected function applySearchId($query)
    {
        return $query
            ->when($this->searchId !== '',
                function ($query) {
                    return $query->where('club_player.club_player_id', $this->searchId);
                });
    }

    protected function applySearchPlayer($query)
    {
        return $query
            ->when($this->searchPlayer !== '',
                function ($query) {
                    return $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$this->searchPlayer.'%']);
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
                    return $query->whereGender($this->searchGender);
                });
    }

    protected function applySearchPlayerStatus($query)
    {
        return $query
            ->when($this->searchPlayerStatus === ModelStatus::Active,
                function ($query) {
                    return $query->whereNull('club_player.deleted_at');
                })
            ->when($this->searchPlayerStatus === ModelStatus::Trashed,
                function ($query) {
                    return $query->whereNotNull('club_player.deleted_at');
                })
            ->when($this->searchPlayerStatus === ModelStatus::All,
                function ($query) {
                    return $query;
                });
    }

    protected function applySearchUser($query)
    {
        return $query
            ->when($this->searchUser === 'withUser',
                function ($query) {
                    return $query->whereHas('users');
                })
            ->when($this->searchUser === 'withInvitation',
                function ($query) {
                    return $query->whereHas('invitations');
                })
            ->when($this->searchUser === 'withoutUserOrInvitation',
                function ($query) {
                    return $query
                        ->whereDoesntHave('users')
                        ->whereDoesntHave('invitations');
                });
    }

    #[Computed]
    public function player_count()
    {
        return $this->club->players()->wherePivotNull('deleted_at')->count();
    }

    #[Computed]
    public function player_only_trashed_count()
    {
        return $this->club->players()->wherePivotNotNull('deleted_at')->count();
    }

    #[Computed]
    public function player_with_trashed_count()
    {
        return $this->club->players()->count();
    }

    #[Computed]
    public function players()
    {
        $query = $this->club->players()
            ->with('users')
            ->withExists('users')
            ->withExists('contestants');

        $query = $this->applySearchId($query);
        $query = $this->applySearchPlayer($query);
        $query = $this->applySearchGender($query);
        $query = $this->applySearchPlayerStatus($query);
        $query = $this->applySearchUser($query);
        $query = $this->applySorting($query);

        // Also eager-load invitation; sorting already handled above
        return $query
                ->paginate($this->perPage);
    }
}; ?>


<x-ui.cards.mobile>
    @if ($this->player_with_trashed_count === 0)
        <flux:text>There are no players yet.</flux:text>
    @else
        <flux:radio.group
            wire:model.live="searchPlayerStatus"
            variant="cards"
            class="flex gap-4"
        >
            <x-radios.club-admin-table-radio
                value="{{ ModelStatus::Active }}"
                heading="Active"
                count="{{ $this->player_count }}"
            />
            <x-radios.club-admin-table-radio
                value="{{ ModelStatus::Trashed }}"
                heading="Archived"
                count="{{ $this->player_only_trashed_count }}"
            />
            <x-radios.club-admin-table-radio
                value="{{ ModelStatus::All }}"
                heading="All"
                count="{{ $this->player_with_trashed_count }}"
            />
        </flux:radio.group>

        <div class="relative">
            <flux:table>

                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'club_player_id'" :direction="$sortDirection" wire:click="sort('club_player_id')" class="w-20">#</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
                    <flux:table.column wire:click="sort('gender')">Gender</flux:table.column>
                    <flux:table.column>
                        <div class="flex items-center gap-1">
                            <flux:icon.user variant="solid" class="size-4 text-green-600" />
                            Owner
                            <flux:badge size="sm" inset="top bottom">can submit results</flux:badge>
                        </div>
                    </flux:table.column>
                    <flux:table.column class="w-0"></flux:table.column>
                </flux:table.columns>

                <flux:table.columns>
                    <flux:table.column>
                        <flux:input wire:model.live.debounce.500ms="searchId" :loading="false" />
                    </flux:table.column>
                    <flux:table.column>
                        <flux:input wire:model.live.debounce.500ms="searchPlayer" :loading="false" />
                    </flux:table.column>
                    <flux:table.column>
                        <flux:select wire:model.live="searchGender" variant="listbox">
                            <flux:select.option value="">All</flux:select.option>
                            @foreach (Gender::cases() as $gender)
                                <flux:select.option value="{{ $gender->value }}">{{ $gender->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.column>
                    <flux:table.column>
                        <flux:select wire:model.live="searchUser" variant="listbox">
                            <flux:select.option value="">All</flux:select.option>
                            <flux:select.option value="withUser">Has Owner</flux:select.option>
                            <flux:select.option value="withInvitation">Has Invitation</flux:select.option>
                            <flux:select.option value="withoutUserOrInvitation">Has No Owner or Invitation</flux:select.option>
                        </flux:select>
                    </flux:table.column>
                    <flux:table.column class="w-0">
                        <flux:tooltip content="Reset">
                            <flux:button wire:click="resetList" icon="list-restart"  />
                        </flux:tooltip>
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows class="hidden" wire:loading.class.remove="hidden" wire:target="searchId,searchPlayer,searchGender,searchUser,searchPlayerStatus,sort">
                    @for ($i = 0; $i < 8; $i++)
                        <flux:table.row>
                            <flux:table.cell class="w-20"><flux:skeleton class="h-6 w-full" /></flux:table.cell>
                            <flux:table.cell><flux:skeleton class="h-6 w-full" /></flux:table.cell>
                            <flux:table.cell class="w-24"><flux:skeleton class="h-6 w-full" /></flux:table.cell>
                            <flux:table.cell><flux:skeleton class="h-6 w-full" /></flux:table.cell>
                            <flux:table.cell class="w-0"><flux:skeleton class="h-6 w-full" /></flux:table.cell>
                        </flux:table.row>
                    @endfor
                </flux:table.rows>

                @if ($this->players->total() > 0)
                    <flux:table.rows wire:loading.class="hidden" wire:target="searchId,searchPlayer,searchGender,searchUser,searchPlayerStatus,sort">
                        @foreach ($this->players as $player)
                            <livewire:tables.rows.club-admin-players-row :$club :$player :key="$player->id" />
                        @endforeach
                    </flux:table.rows>
                @endif
            </flux:table>

            @if ($this->players->hasMorePages())
                <div
                    wire:loading.remove
                    x-data="{ armed: false }"
                    x-init="const scroller = $el.closest('ui-table-scroll-area'); const target = scroller ?? window; target.addEventListener('scroll', () => { armed = true }, { once: true })"
                    x-intersect="if (armed) $wire.loadMore()"
                    class="py-4"
                ></div>
            @endif

            <div wire:loading.flex wire:target="loadMore" class="flex justify-center py-4">
                <flux:icon.loading class="size-8 opacity-50" />
            </div>

            @if ($this->players->total() === 0)
                <div wire:loading.remove wire:target="searchId,searchPlayer,searchGender,searchUser,searchPlayerStatus,sort">
                    <x-tables.items-not-found colspan="5" collectionName="{{ $searchPlayerStatus->label() }} members" />
                </div>
            @endif
        </div>
    @endif
</x-ui.cards.mobile>