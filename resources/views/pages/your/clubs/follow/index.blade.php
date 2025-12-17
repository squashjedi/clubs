<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts::user')] class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'clubs' => auth()->user()
                ->clubs()
                ->orderBy('name')
                ->paginate(10),
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.clubs-heading')

    <x-clubs.layout :heading="__('Follow')" :subheading="__('Clubs that you follow.')">
        @if (session()->has('message'))
            <flux:callout variant="success" icon="check-circle" class="mb-6" inline x-data="{ visible: true }" x-show="visible">
                <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
                <x-slot name="controls">
                    <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
                </x-slot>
            </flux:callout>
        @endif

        <flux:table :paginate="$clubs">
            <flux:table.columns>
                <flux:table.column>Club</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @if ($clubs->count() > 0)
                    @foreach ($clubs as $club)
                        <flux:table.row wire:key="{{ $club->id }}">
                            <flux:table.cell>
                                <flux:link href="{{ route('club', [$club]) }}" variant="subtle" wire:navigate>{{ $club->name }}</flux:link>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @else
                    <flux:table.row>
                        <flux:table.cell>
                            You don't follow any club.
                        </flux:table.cell>
                    </flux:table.row>
                @endif
            </flux:table.rows>
        </flux:table>

    </x-clubs.layout>
</section>
