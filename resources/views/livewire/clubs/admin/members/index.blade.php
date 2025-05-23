<?php

use App\Models\Club;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
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
            $query->orderBy('club_member_id', $this->sortDirection);
        } elseif ($this->sortBy === 'name') {
            $query->orderBy('last_name', $this->sortDirection)->orderBy('first_name', $this->sortDirection);
        }

        return $query;
    }

    protected function applySearch($query)
    {
        return $this->search === ''
            ? $query
            : $query->where(function (Builder $query) {
                $query->where('members.club_member_id', $this->search)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$this->search.'%']);
            });
    }

    public function with(): array
    {
        $query = $this->club->members();
        $query = $this->applySearch($query);
        $query = $this->applySorting($query);

        return [
            'member_count' => $this->club->members()->count(),
            'members' => $query->with('invitation')->orderBy('last_name')->orderBy('first_name')->paginate(20),
        ];
    }
}; ?>


<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item :href="route('clubs.admin', [$club])" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __("Members") }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex justify-between">
        <x-ui.typography.h3>{{ __("Members") }}</x-ui.typography.h3>
        <flux:button :href="route('clubs.admin.members.create', [$club])" variant="primary" icon="plus" wire:navigate>Member</flux:button>
    </div>

    @if ($member_count === 0)
        <div>There are no members yet.</div>
    @else
        <flux:input wire:model.live.debounce.500ms="search" icon="magnifying-glass" placeholder="Search # or member" class="max-w-md" />

        @if ($members->total() > 0)
            <div class="relative">
                <flux:table :paginate="$members">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')" class="w-4">#</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
                        <flux:table.column>User</flux:table.column>
                        <flux:table.column class="w-0"></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($members as $member)
                            <livewire:clubs.admin.members.index-row :$club :$member :key="$member->id" />
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div wire:loading wire:target="sort" class="absolute inset-0 bg-white opacity-50"></div>
                <div wire:loading.flex wire:target="sort" class="flex items-center justify-center absolute inset-0">
                    <flux:icon.loading class="size-10 text-gray-500" />
                </div>
            </div>
        @else
            <div>Sorry, no results found for '{{ $search }}'.</div>
        @endif
    @endif
</div>