<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Sport;
use App\Models\League;
use Livewire\Component;
use App\Livewire\Forms\LeagueForm;
use Illuminate\Support\Collection;

new class extends Component {
    public Club $club;

    public ?League $league;

    public LeagueForm $form;

    public array $sports;

    public bool $confirmSave = false;

    public string $confirmation = '';

    public array $tallyUnits;

    public $isEdit = false;

    public function mount()
    {
        $this->sports = Sport::orderBy('name')->get()->toArray();

        if ($this->isEdit) {
            $this->form->setLeague($this->league);
            $this->tallyUnits = Sport::find($this->form->sport_id)->tallyUnits()->withPivot('max_best_of')->get()->toArray();
        }
    }

    public function confirmCreate()
    {
        // Validate only — do NOT create yet
        $this->form->validate();

        // Open the confirm modal
        $this->confirmSave = true;
    }

    public function updatedFormSportId()
    {
        if (! $this->form->sport_id) {
            return;
        }

        $this->tallyUnits = Sport::find($this->form->sport_id)->tallyUnits()->withPivot('max_best_of')->get()->toArray();

        $this->form->tally_unit_id = $this->tallyUnits[0]['id'];

        $this->resetErrorBag('form.sport_id');
        $this->resetErrorBag('form.tally_unit_id');
    }

    public function restore()
    {
        $this->league->restore();

        Flux::toast(
            variant: "success",
            text: "League restored."
        );
    }

    public function archive()
    {
        $this->league->delete();

        Flux::toast(
            variant: "success",
            text: "League archived."
        );
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

    public function save()
    {
        if (! $this->isEdit) {
            $league = $this->form->store($this->club);

            $this->confirmSave = false;

            Flux::toast(
                variant: "success",
                text: "{$league->name} created."
            );

            $this->redirectRoute('club.admin.leagues.sessions.create', ['club' => $this->club, 'league' => $league], navigate: true);

            return;
        }

        $this->form->update();

        Flux::toast(
            variant: 'success',
            text: 'League updated.'
        );

        $this->redirectRoute('club.admin.leagues.edit', ['club' => $this->club, 'league' => $this->league], navigate: true);
    }
}; ?>

<div class="space-y-main">
    <x-headings.page-heading>
        @if (empty($league))
            Create New League
        @else
            <div class="flex-1 sm:flex sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                <div class="flex items-center gap-2 h-10">
                    <div>{{ $league->name }}</div>
                    @if ($league->trashed())
                        <flux:badge color="red" variant="solid">{{ __('Archived') }}</flux:badge>
                    @endif
                </div>
                @if (! $league->trashed())
                    @if ($league->latestSession)
                        <flux:button
                            href="{{ route('club.admin.leagues.sessions.show', [$club, $league, 'session' => $league->latestSession]) }}"
                            variant="filled"
                            icon:trailing="arrow-right"
                            wire:navigate
                        >
                            {{ __('Latest Session') }}
                        </flux:button>
                    @else
                        <flux:button
                            href="{{ route('club.admin.leagues.sessions.create', [$club, $league]) }}"
                            icon="plus"
                            variant="primary"
                            wire:navigate
                        >
                            {{ __('Session') }}
                        </flux:button>
                    @endif
                @endif
            </div>
        @endif
    </x-headings.page-heading>

    <form wire:submit="save" class="space-y-6">

        <flux:input wire:model="form.name" label="Name" placeholder="{{ __('Squash Box League') }}" class="max-w-sm" required />

        @if (! $isEdit)
            <flux:select wire:model.live="form.sport_id" label="Sport" variant="listbox" searchable placeholder="Choose sport..." class="max-w-sm">
                @foreach ($sports as $sport)
                    <flux:select.option value="{{ $sport['id'] }}">{{ $sport['name'] }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($form->sport_id)
                <flux:field>
                    <flux:label>{{ __('Best of') }}</flux:label>
                    <flux:input.group>
                        <flux:select
                            wire:model="form.best_of"
                            variant="listbox"
                            placeholder="Select..."
                            class="!w-fit"
                        >
                            <template x-for="i in $wire.tallyUnits.find(tallyUnit => tallyUnit.id == $wire.form.tally_unit_id).pivot.max_best_of" :key="i">
                                <flux:select.option x-text="i"></flux:select.option>
                            </template>
                        </flux:select>

                        <flux:select
                            wire:model="form.tally_unit_id"
                            variant="listbox"
                            placeholder="Select..."
                            x-data
                            x-init="
                                $watch('$wire.form.tally_unit_id', function (value) {
                                    if ($wire.form.best_of <= $wire.tallyUnits.find(tallyUnit => tallyUnit.id == $wire.form.tally_unit_id).pivot.max_best_of) {
                                        return
                                    }
                                    return $wire.form.best_of = $wire.tallyUnits.find(tallyUnit => tallyUnit.id == value).pivot.max_best_of
                                })
                            "
                            class="!w-fit"
                        >
                            @foreach ($tallyUnits as $tallyUnit)
                                <flux:select.option value="{{ $tallyUnit['id'] }}">{{ $tallyUnit['key'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:input.group>
                    <flux:error name="form.best_of" />
                </flux:field>
            @endif
        @else
            <flux:field>
                <flux:label>{{ __('Sport') }}</flux:label>
                <flux:input class="max-w-sm" disabled value="{{ $league->sport->name }}" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Best of') }}</flux:label>
                <flux:input.group>
                    <flux:select
                        wire:model="form.best_of"
                        variant="listbox"
                        placeholder="Select..."
                        class="!w-fit"
                        :disabled="true"
                    >
                        <template x-for="i in $wire.tallyUnits.find(tallyUnit => tallyUnit.id == $wire.form.tally_unit_id).pivot.max_best_of" :key="i">
                            <flux:select.option x-text="i"></flux:select.option>
                        </template>
                    </flux:select>
                    <flux:select
                        wire:model="form.tally_unit_id"
                        variant="listbox"
                        placeholder="Select..."
                        x-data
                        x-init="
                            $watch('$wire.form.tally_unit_id', function (value) {
                                if ($wire.form.best_of <= $wire.tallyUnits.find(tallyUnit => tallyUnit.id == $wire.form.tally_unit_id).pivot.max_best_of) {
                                    return
                                }
                                return $wire.form.best_of = $wire.tallyUnits.find(tallyUnit => tallyUnit.id == value).pivot.max_best_of
                            })
                        "
                        class="!w-fit"
                        :disabled="true"
                    >
                        @foreach ($tallyUnits as $tallyUnit)
                            <flux:select.option value="{{ $tallyUnit['id'] }}">{{ $tallyUnit['key'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:input.group>
                <flux:error name="form.best_of" />
            </flux:field>
        @endif

        <div class="flex gap-2">

            @teleport('body')
                <flux:modal
                    wire:model="confirmSave"
                    class="modal"
                >
                    <form wire:submit="save" class="space-y-6">
                        <x-modals.content>
                            <x-slot:heading>Create New League</x-slot:heading>
                            <flux:text>Are you sure you wish to create this league?</flux:text>

                            <div class="space-y-1.5 pt-3 pb-2">
                                <flux:table class="border">
                                    <flux:table.rows>
                                        <flux:table.row>
                                            <flux:table.cell class="!w-0 bg-stone-100 border-r">
                                                League Name
                                            </flux:table.cell>
                                            <flux:table.cell class="text-zinc-900 whitespace-normal">
                                                {{ $form->name }}
                                            </flux:table.cell>
                                        </flux:table.row>
                                        <flux:table.row>
                                            <flux:table.cell class="!w-0 bg-stone-100 border-r">
                                                <div class="flex items-center gap-1">
                                                    <div>Sport</div>
                                                </div>
                                            </flux:table.cell>
                                            <flux:table.cell class="flex items-center gap-1 text-zinc-900">
                                                <span x-text="$wire.sports.find(sport => sport.id == $wire.form.sport_id).name"></span>
                                                <flux:icon.exclamation-triangle variant="micro" class="size-4 text-amber-500" />

                                            </flux:table.cell>
                                        </flux:table.row>
                                        <flux:table.row>
                                            <flux:table.cell class="!w-0 bg-stone-100 border-r">
                                                <div class="flex items-center gap-1">
                                                    <div>Best of</div>
                                                </div>
                                            </flux:table.cell>
                                            <flux:table.cell class="flex items-center gap-1 text-zinc-900">
                                                {{ $form->best_of }} <span x-text="$wire.tallyUnits.find(tallyUnit => tallyUnit.id == $wire.form.tally_unit_id).key"></span>
                                                <flux:icon.exclamation-triangle variant="micro" class="size-4 text-amber-500" />
                                            </flux:table.cell>
                                        </flux:table.row>
                                    </flux:table.rows>
                                </flux:table>
                                <div class="flex flex-col items-end">
                                    <div>
                                        <flux:icon.exclamation-triangle variant="micro" class="size-4 text-amber-500 inline-block"/>
                                        <span class="text-zinc-500 text-xs">Not updatable once league is created.</span>
                                    </div>
                                </div>
                            </div>
                            <x-slot:buttons>
                                <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
                            </x-slot:buttons>
                        </x-modals.content>
                    </form>
                </flux:modal>
            @endteleport

            @if ($isEdit)
                <flux:button type="submit" variant="primary">
                    Update
                </flux:button>

                <flux:dropdown>
                    <flux:tooltip content="Options">
                        <flux:button icon:trailing="ellipsis-horizontal" square />
                    </flux:tooltip>

                    <flux:menu>
                        @if ($league->trashed())
                            <flux:menu.item
                                wire:click="restore"
                                icon="arrow-path"
                                icon:variant="outline"
                            >
                                {{ __('Restore') }}
                            </flux:menu.item>
                        @else
                            <flux:menu.item
                                wire:click="archive"
                                icon="no-symbol"
                                icon:variant="outline"
                            >
                                {{ __('Archive') }}
                            </flux:menu.item>
                        @endif

                        <flux:menu.separator />

                        <flux:modal.trigger name="delete">
                            <flux:menu.item
                                x-on:click="$js.openModal"
                                variant="danger"
                                icon="trash"
                                icon:variant="outline"
                            >
                                Delete
                            </flux:menu.item>
                        </flux:modal.trigger>

                        @teleport('body')
                            <flux:modal name="delete" class="modal">
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
            @else
                <flux:button type="button" variant="primary" wire:click="confirmCreate">
                    Create
                </flux:button>
            @endif
        </div>
    </form>
</div>

@script
<script>
    $js('openModal', () => {
        document.querySelector('[x-ref="error-message"]').classList.add('hidden');
        document.querySelector('[x-ref="error-input"]').classList.remove('border-red-500');
        $wire.confirmation = '';
    })
</script>
@endscript