<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\Session;
use App\Models\Division;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
{
    public Club $club;

    public Session $session;

    public Division $division;

    public Collection $availableMembers;

    public int $promoteCount;

    public int $relegateCount;

    public ?array $selectedMember = [
        'id' => null
    ];

    public function mount()
    {
        $this->availableMembers = $this->getAvailableMembers();
        $this->promoteCount = $this->division->promote_count;
        $this->relegateCount = $this->division->relegate_count;
    }

    protected function getAvailableMembers() {
        $excludedIds = $this->session->membersInSession();
        $availableMembers = $this->club->members()->whereNotIn('id', $excludedIds)->sortByName()->get();

        return $availableMembers;
    }

    public function addMember()
    {
        $divisionContestants = $this->division->contestants();

        DB::transaction(function () use ($divisionContestants) {
            $divisionContestants->create([
                'league_session_id' => $this->session->id,
                'division_id' => $this->division->id,
                'member_id' => $this->selectedMember['id'],
                'index' => $divisionContestants->max('index') + 1
            ]);

            $this->division->increment('contestant_count');
        });

        $this->selectedMember['id'] = null;

        $this->availableMembers = $this->getAvailableMembers();

        $this->dispatch('update-division');

        Flux::modals()->close('add-member');

        Flux::toast(
            variant: 'success',
            text: 'Competitor added.'
        );
    }

    #[On('update-available-members')]
    public function updateAvailableMembers()
    {
        $this->availableMembers = $this->getAvailableMembers();
    }

    #[Renderless]
    public function updatedPromoteCount()
    {
        $this->division->update([
            'promote_count' => $this->promoteCount,
        ]);

        $this->dispatch('update-division');

        Flux::toast(
            variant: 'success',
            text: 'Promote count updated.'
        );
    }

    #[Renderless]
    public function updatedRelegateCount()
    {
        $this->division->update([
            'relegate_count' => $this->relegateCount,
        ]);

        $this->dispatch('update-division');

        Flux::toast(
            variant: 'success',
            text: 'Relegate count updated.'
        );
    }
}; ?>

<div>
    @php
        $tier = $division->tier;
        $tierCount = $session->tiers()->count();
        $showPromote = $tier->index > 0;
        $showPromoteWarning = $division->promote_count === 0;
        $showRelegate = $tier->index + 1 < $tierCount;
        $showRelegateWarning = $division->relegate_count === 0;
    @endphp
    @if ($showPromote || $showRelegate || is_null($session->processed_at))
        <div class="flex flex-col sm:flex-row items-center sm:justify-between min-h-10">
            @if (is_null($session->processed_at))
                <div>
                    <flux:modal.trigger name="add-member">
                        <flux:button
                            variant="primary"
                            icon="plus"
                        >
                            Competitor
                        </flux:button>
                    </flux:modal.trigger>

                    @teleport('body')
                        <flux:modal name="add-member" class="modal">
                            <form wire:submit="addMember">
                                <x-modals.content>
                                    <x-slot:heading>{{ __('Add Competitor') }}</x-slot:heading>
                                    @if (count($availableMembers) === 0)
                                        <flux:callout variant="secondary" icon="information-circle">
                                            {{ __('All members are competing in this league session.') }}
                                        </flux:callout>
                                    @else
                                        <flux:text>Select the member you wish to add to this division.</flux:text>
                                        <flux:field>
                                            <flux:select variant="listbox" wire:model="selectedMember.id" searchable clearable placeholder="Select member...">
                                                @foreach ($availableMembers as $member)
                                                    <flux:select.option value="{{ $member->id }}">
                                                        <x-generic.member :$club :$member :isLink="false" />
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </flux:field>
                                        <x-slot:buttons>
                                            <flux:button
                                                type="submit"
                                                variant="primary"
                                                x-bind:disabled="!$wire.selectedMember.id"
                                                :loading="false"
                                            >
                                                Add
                                            </flux:button>
                                        </x-slot:buttons>
                                    @endif
                                </x-modals.content>
                            </form>
                        </flux:modal>
                    @endteleport
                </div>
            @endif
            @if ($showPromote || $showRelegate)
                <div class="sm:flex sm:items-center sm:justify-center gap-6 space-y-3 sm:space-y-0 mt-8 sm:mt-0">
                    @if ($showPromote)
                        <div class="flex flex-col items-end">
                            <div class="flex items-center gap-1">
                                <flux:text>Promote</flux:text>
                                <flux:icon.arrow-up variant="mini" class="size-5 text-green-600" />
                                @if (is_null($session->processed_at))
                                    <flux:select
                                        wire:model.live="promoteCount"
                                        @class([
                                            '!border-amber-300 !bg-amber-50' => $showPromoteWarning,
                                            'cursor-not-allowed' => $session->processed_at
                                        ])
                                        :disabled="! is_null($session->processed_at)"
                                    >
                                        @foreach (range(0, $division->contestant_count - $division->relegate_count) as $i)
                                            <flux:select.option>{{ $i }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @if ($division->promote_count === 0)
                                        <flux:icon.exclamation-circle variant="mini" class="text-amber-500" />
                                    @endif
                                @else
                                    <flux:text>{{ $division->promote_count }}</flux:text>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($showRelegate)
                        <div class="flex flex-col items-end">
                            <div class="flex items-center gap-1">
                                <flux:text>Relegate</flux:text>
                                <flux:icon.arrow-down variant="mini" class="size-5 text-red-600" />
                                @if (is_null($session->processed_at))
                                    <flux:select
                                        wire:model.live="relegateCount"
                                        @class([
                                            '!border-amber-300 !bg-amber-50' => $showRelegateWarning,
                                            'cursor-not-allowed' => $session->processed_at
                                        ])
                                        :disabled="! is_null($session->processed_at)"
                                    >
                                        @foreach (range(0, $division->contestant_count - $division->promote_count) as $i)
                                            <flux:select.option>{{ $i }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @if ($division->relegate_count === 0)
                                        <flux:icon.exclamation-circle variant="mini" class="text-amber-500" />
                                    @endif
                                @else
                                    <flux:text>{{ $division->relegate_count }}</flux:text>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
