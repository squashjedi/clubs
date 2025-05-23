<?php

use Flux\Flux;
use App\Models\Club;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.user')] class extends Component
{
    use WithPagination;

    public string $tab = 'member';

    public function delete(Club $club)
    {
        Flux::modal('delete-club-'.$club->id)->close();

        $club->delete();

        Flux::toast(
            variant: 'success',
            text: $club->name.' has been deleted.',
        );

        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'clubs' => auth()->user()->clubsAdmin()->orderBy('name')->paginate(10),
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.clubs-heading')

    <x-clubs.layout :heading="__('Administrate')" :subheading="__('Clubs that you administrate.')">

        <flux:table :paginate="$clubs">
            <flux:table.columns>
                <flux:table.column>Club</flux:table.column>
                <flux:table.column class="w-0"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @if ($clubs->count() > 0)
                    @foreach ($clubs as $club)
                    <flux:table.row wire:key="{{ $club->id }}">
                        <flux:table.cell>
                            <flux:link href="{{ route('clubs.front', [ $club ]) }}" variant="subtle" wire:navigate>{{ $club->name }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell class="flex justify-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="bottom" />

                                <flux:menu>
                                    <flux:menu.item :href="route('clubs.front', [ $club ])" icon="eye" icon-variant="outline" wire:navigate>View</flux:navmenu.item>
                                    <flux:menu.item :href="route('clubs.admin', [ $club ])" icon="pencil" icon-variant="outline" wire:navigate>Admin</flux:navmenu.item>
                                </flux:navmenu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                @else
                    <flux:table.row>
                        <flux:table.cell>
                            You don't administrate any club.
                        </flux:table.cell>
                        <flux:table.cell></flux:table.cell>
                    </flux:table.row>
                @endif
            </flux:table.rows>
        </flux:table>

    </x-clubs.layout>
</section>
