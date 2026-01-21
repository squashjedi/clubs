<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\League;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    public Club $club;

    public League $league;

    public ?int $currentSessionId = null;

    public bool $onSessionsCreate = false;

    public ?string $route = null;

    #[Validate('required')]
    public string $leagueName;

    public bool $editing = false;

    public string $confirmation = '';

    public function mount()
    {
        $this->route = request()->fullUrl();
        $this->leagueName = $this->league->name;

        $this->currentSessionId = request()->route('session')?->id; // might be null
        $this->onSessionsCreate = request()->routeIs('club.admin.leagues.sessions.create');
    }

    public function save()
    {
        $this->validate();

        $this->league->update([
            'name' => $this->leagueName,
        ]);

        $this->editing = false;

        Flux::toast(
            variant: "success",
            text: "League name updated."
        );
    }

    public function archive()
    {
        $this->league->delete();

        Flux::toast(
            variant: "success",
            text: "League archived."
        );

        $this->redirectRoute('club.admin.leagues.edit', ['club' => $this->club, 'league' => $this->league], navigate: true);
    }

    public function delete()
    {
        if ($this->confirmation !== 'CONFIRM') {
            $this->addError('confirmation', "Must be identical to 'CONFIRM'");
            return;
        }

        $this->league->forceDelete();

        Flux::toast(
            variant: "success",
            text: "League deleted."
        );

        $this->redirectRoute('club.admin.leagues', ['club' => $this->club], navigate: true);
    }
}; ?>

<div
    x-data="{ name: {{ json_encode($league->name) }} }"
    class="relative"
>
    <div class="sm:flex sm:items-center sm:gap-2 sm:justify-between space-y-1 sm:space-y-0">
        <div class="sm:flex sm:flex-row-reverse sm:items-center sm:gap-2 space-y-1 sm:space-y-0">
            <div x-show="!$wire.editing" class="flex items-center gap-2 min-h-10">

                <x-headings.page-heading>{{ $league->name }}</x-headings.page-heading>
                <div class="flex items-center">
                    <flux:button
                        variant="subtle"
                        @click="$wire.editing = true"
                        icon="pencil-square"
                        icon:variant="outline"
                        size="sm"
                    />

                    <flux:dropdown>
                        <flux:tooltip content="Options">
                            <flux:button
                                variant="subtle"
                                size="sm"
                                icon:trailing="ellipsis-horizontal"
                                square
                            />
                        </flux:tooltip>

                        <flux:menu>
                            <flux:modal.trigger name="archive">
                                <flux:menu.item
                                    icon="no-symbol"
                                    icon:variant="outline"
                                >
                                    {{ __('Archive') }}
                                </flux:menu.item>
                            </flux:modal.trigger>

                            @teleport('body')
                                <flux:modal name="archive" class="modal">
                                    <form wire:submit="archive">
                                        <x-modals.content>
                                            <x-slot:heading>{{ __('Archive') }} {{ __('League') }}</x-slot:heading>
                                            Are you sure you wish to archive this league?
                                            <x-slot:buttons>
                                                <flux:button type="submit" variant="primary">{{ __('Archive') }}</flux:button>
                                            </x-slot:buttons>
                                        </x-modals.content>
                                    </form>
                                </flux:modal>
                            @endteleport

                            <flux:menu.separator />

                            <flux:modal.trigger name="delete-league">
                                <flux:menu.item
                                    x-on:click="$js.openModal;$flux.modal('delete-league').show();$wire.confirmation = ''"
                                    variant="danger"
                                    icon="trash"
                                    icon:variant="outline"
                                >
                                    Delete
                                </flux:menu.item>
                            </flux:modal.trigger>

                            @teleport('body')
                                <flux:modal name="delete-league" class="modal">
                                    <form wire:submit="delete">
                                        <x-modals.content>
                                            <x-slot:heading>{{ __('Delete') }} {{ __('League') }}</x-slot:heading>
                                            <flux:text>Are you sure you wish to delete this league?</flux:text>
                                            <flux:text>Once this league is deleted, all of it's resources and data will be permanently deleted.</flux:text>
                                            <flux:text>Type '<span class="font-medium text-zinc-900">CONFIRM</span>'' to permanently delete this league.</flux:text>
                                            <flux:field>
                                                <flux:input
                                                    x-ref="error-input"
                                                    wire:model="confirmation"
                                                />
                                                <flux:error
                                                    x-ref="error-message"
                                                    name="confirmation"
                                                />
                                            </flux:field>
                                            <x-slot:buttons>
                                                <flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button>
                                            </x-slot:buttons>
                                        </x-modals.content>
                                    </form>
                                </flux:modal>
                            @endteleport
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
            <flux:input.group x-cloak x-show="$wire.editing" class="h-10 max-w-xs" @click.outside="$wire.editing = false;$wire.leagueName = name;">
                <flux:input wire:model="leagueName" class="flex-1 w-xs" />
                <flux:button
                    wire:click="save"
                    x-bind:disabled="$wire.leagueName.length === 0;"
                    icon="check"
                    variant="primary"
                />
            </flux:input.group>
            @if ($league->sessions()->exists())
                <flux:dropdown>
                    <flux:button
                        icon="ellipsis-vertical"
                    />

                    <flux:menu>
                        <flux:menu.item href="{{ route('club.admin.leagues.sessions', [$club, $league]) }}" wire:navigate>Sessions</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
        @if (
            $this->currentSessionId !== $league->latestSession?->id
            && ! $this->onSessionsCreate
        )
            <flux:button
                href="{{ route('club.admin.leagues.sessions.show', [$club, $league, 'session' => $league->latestSession]) }}"
                variant="filled"
                icon:trailing="arrow-right"
                class="mt-2 mb-3 sm:my-0"
                wire:navigate
            >
                {{ __('Latest Session') }}
            </flux:button>
        @endif
    </div>
    <div.flex wire:loading class="absolute inset-0" />
</div>

<script>
    this.$js.openModal = () => {
        console.log('working!')
        document.querySelector('[x-ref="error-message"]').classList.add('hidden');
        document.querySelector('[x-ref="error-input"]').classList.remove('border-red-500');
        $wire.confirmation = '';
    }
</script>