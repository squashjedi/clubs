<?php

use App\Enums\Gender;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Enums\PlayerRelationship;

new #[Layout('layouts.user')] class extends Component
{
    public function with(): array
    {
        return [
            'players' => Auth::user()->players()->wherePivot('relationship', 'guardian')->orderByDesc('id')->get(),
        ];
    }
}; ?>

<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Players')" :subheading=" __('Update the appearance settings for your account')">
        <flux:button
            href="{{ route('settings.players.create') }}"
            variant="primary"
            icon="plus"
            wire:navigate
        >
            Player
        </flux:button>
        <div class="my-6 space-y-6 divide-y">
            @foreach ($players as $player)
                <div class="space-y-4">
                    @if ($player->pivot->relationship === PlayerRelationship::Self)
                        <div>You</div>
                    @endif
                    <flux:heading size="lg">Player ID: {{ $player->id }}</flux:heading>

                    <div class="space-y-3">
                        <flux:heading size="lg">{{ $player->name }}</flux:heading>

                        <table>
                            <tr>
                                <td>
                                    <flux:text>Gender:</flux:text>
                                </td>
                                <td class="pl-2">
                                    <flux:text class="text-zinc-900">{{ $player->gender === Gender::Unknown ? '-' : $player->gender->label() }}</flux:text>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <flux:text>Date of Birth:</flux:text>
                                </td>
                                <td class="pl-2">
                                    <flux:text class="text-zinc-900">{{ $player->dob?->format('jS M Y') ?? '-' }}</flux:text>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <flux:text>Email:</flux:text>
                                </td>
                                <td class="pl-2">
                                    <flux:text class="text-zinc-900">{{ $player->email }}</flux:text>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <flux:text>Tel No:</flux:text>
                                </td>
                                <td class="pl-2">
                                    <flux:text class="text-zinc-900">{{ $player->tel_no }}</flux:text>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <flux:button
                        href="{{ route('settings.players.edit', [$player]) }}"
                        variant="primary"
                    >
                        Edit
                    </flux:button>
                </div>
            @endforeach
        </div>
    </x-settings.layout>
</div>
