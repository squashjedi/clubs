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

    public function deleteTables()
    {
        DB::transaction(function () {
            $this->session->removeStructure();

            $this->session->update([
                'built_at' => null,
            ]);

            $this->session->update([
                'published_at' => null,
            ]);

            Flux::modals()->close();

            Flux::toast(
                variant: 'success',
                text: 'Tables deleted.'
            );
        });

        return $this->redirectRoute('club.admin.leagues.sessions.structure', [ 'club' => $this->club, 'league' => $this->league, 'session' => $this->session ], navigate: true);
    }

}; ?>

<div>
    <flux:modal.trigger name="delete-tables">
        <flux:tooltip>
            <flux:button
                variant="subtle"
                icon:variant="outline"
                icon="trash"
            />
            <flux:tooltip.content>Delete Tables</flux:tooltip.content>
        </flux:tooltip>
    </flux:modal.trigger>

    @teleport('body')
        <flux:modal name="delete-tables" class="modal">
            <form wire:submit="deleteTables" class="space-y-6">
                <x-modals.content>
                    <x-slot:heading>{{ __('Delete Tables') }}</x-slot:heading>
                    <flux:text>This action will permanently delete all results and revert back to the Session Builder.</flux:text>
                    <flux:text>Are you sure you wish to delete all the tables?</flux:text>

                    <x-slot:buttons>
                        <flux:button type="submit" variant="danger">Delete</flux:button>
                    </x-slot:buttons>
                </x-modals.content>
            </form>
        </flux:modal>
    @endteleport
</div>