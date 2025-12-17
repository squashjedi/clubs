<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public function deleteSession()
    {
        DB::transaction(function () {
            $this->session->removeStructure();

            $this->session->entrants()->forceDelete();

            $this->session->forceDelete();

            Flux::toast(
                variant: 'success',
                text: 'Session deleted.'
            );

            $this->redirect(route('club.admin.leagues.sessions', ['club' => $this->club, 'league' => $this->league]), navigate: true);
        });
    }
}; ?>

<div class="flex items-center justify-between gap-4">
    <x-headings.page-heading>Session Builder</x-headings.page-heading>
    <flux:modal.trigger name="delete-session">
        <flux:tooltip>
            <flux:button
                icon="trash"
                icon:variant="outline"
                variant="subtle"
            />
            <flux:tooltip.content>Delete Session</flux:tooltip.content>
        </flux:tooltip>
    </flux:modal.trigger>

    @teleport('body')
        <flux:modal name="delete-session" class="modal">
            <form wire:submit="deleteSession" class="space-y-6">
                <x-modals.content>
                    <x-slot:heading>{{ __('Delete Session') }}</x-slot:heading>
                    <flux:text>Are you sure you wish to permanently delete this session?</flux:text>

                    <x-slot:buttons>
                        <flux:button type="submit" variant="danger">Delete</flux:button>
                    </x-slot:buttons>
                </x-modals.content>
            </form>
        </flux:modal>
    @endteleport
</div>
