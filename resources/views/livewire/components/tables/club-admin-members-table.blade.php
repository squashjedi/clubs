<?php

use App\Models\Club;
use App\Enums\ModelStatus;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new class extends Component
{
    use WithPagination;

    public Club $club;

    #[Url]
    public string $searchId = '';

    #[Url]
    public string $searchMember = '';

    #[Url]
    public string $searchUser = '';

    #[Url]
    public ModelStatus $searchMemberStatus = ModelStatus::Active;

    #[Url]
    public $sortBy = 'club_member_id';

    #[Url]
    public $sortDirection = 'desc';

    #[On('delete')]
    public function delete() {}

    public function resetList()
    {
        $this->reset('searchId', 'searchMember', 'searchUser', 'sortBy', 'sortDirection');
    }

    public function updatedSearchMemberStatus()
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
            ->when($this->sortBy === 'club_member_id',
                function ($query) {
                    return $query->orderBy('club_member_id', $this->sortDirection);
                })
            ->when($this->sortBy === 'name',
                function ($query) {
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
                    return $query->where('members.club_member_id', $this->searchId);
                });
    }

    protected function applySearchMember($query)
    {
        return $query
            ->when($this->searchMember !== '',
                function ($query) {
                    return $query->WhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$this->searchMember.'%']);
                });
    }

    protected function applySearchMemberStatus($query)
    {
        return $query
            ->when($this->searchMemberStatus === ModelStatus::Trashed,
                function ($query) {
                    return $query->onlyTrashed();
                })
                ->when($this->searchMemberStatus === ModelStatus::All,
                function ($query) {
                    return $query->withTrashed();
                });
    }

    protected function applySearchUser($query)
    {
        return $query
            ->when($this->searchUser === 'withUser',
                function ($query) {
                    return $query->whereHas('user');
                })
            ->when($this->searchUser === 'withInvitation',
                function ($query) {
                    return $query->whereHas('invitation');
                })
            ->when($this->searchUser === 'withoutUserOrInvitation',
                function ($query) {
                    return $query
                        ->whereDoesntHave('user')
                        ->whereDoesntHave('invitation');
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
        $query = $this->club->members()->with('user')->withCount('contestants');
        $query = $this->applySearchId($query);
        $query = $this->applySearchMember($query);
        $query = $this->applySearchMemberStatus($query);
        $query = $this->applySearchUser($query);
        $query = $this->applySorting($query);

        return $query->with('invitation')->orderBy('last_name')->orderBy('first_name')->paginate(20);
    }
}; ?>

<div class="space-y-6">
    @if ($this->member_all_count === 0)
        <flux:text>There are no members yet.</flux:text>
    @else
        <flux:radio.group wire:model.live="searchMemberStatus" variant="cards" :indicator="false" class="flex gap-4">
            <x-clubs.backend.indexs.radio value="{{ ModelStatus::Active }}" heading="Active" count="{{ $this->member_active_count }}" />
            <x-clubs.backend.indexs.radio value="{{ ModelStatus::Trashed }}" heading="Archived" count="{{ $this->member_trashed_count }}" />
            <x-clubs.backend.indexs.radio value="{{ ModelStatus::All }}" heading="All" count="{{ $this->member_all_count }}" />
        </flux:radio.group>

        <div class="relative">
            <flux:table :paginate="$this->members">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'club_member_id'" :direction="$sortDirection" wire:click="sort('club_member_id')" class="w-20">#</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Member</flux:table.column>
                    <flux:table.column>
                        <div class="flex items-center gap-1">
                            <flux:icon.user variant="solid" class="size-4 text-green-600" />
                            User
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
                        <flux:input wire:model.live.debounce.500ms="searchMember" :loading="false" />
                    </flux:table.column>
                    <flux:table.column>
                        <flux:select wire:model.live="searchUser" variant="listbox">
                            <flux:select.option value="">All</flux:select.option>
                            <flux:select.option value="withUser">Has User</flux:select.option>
                            <flux:select.option value="withInvitation">Has Invitation</flux:select.option>
                            <flux:select.option value="withoutUserOrInvitation">No User or Invitation</flux:select.option>
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
                            <livewire:components.tables.rows.club-admin-members-row :$club :$member :key="$member->id" />
                        @endforeach
                    </flux:table.rows>
                @else
                    <x-tables.items-not-found colspan="4" collectionName="{{ $searchMemberStatus->label() }} members" />
                @endif
            </flux:table>

            <div wire:loading class="absolute inset-0 bg-white opacity-50" />
        </div>
    @endif
</div>