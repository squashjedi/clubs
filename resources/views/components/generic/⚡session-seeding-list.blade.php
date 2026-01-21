<?php

use Flux\Flux;
use App\Models\Club;
use App\Models\League;
use App\Models\Player;
use App\Models\Session;
use Livewire\Component;
use App\Models\Contestant;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public ?Collection $initialEntrants;

    public Collection $entrants;

    #[Validate('required', message: 'A player is required.')]
    public ?array $selectedPlayer = [
        'id' => null,
        'rank' => null,
    ];

    public function mount()
    {
        $this->entrants = $this->getEntrants();
    }

    public function getEntrants()
    {
        return $this->session->entrants()
            ->with(['player' => function ($q) {
                $q->select('players.*')
                    ->selectRaw('EXISTS (
                            SELECT 1 FROM player_user pu
                            WHERE pu.player_id = players.id
                        ) as has_user')
                    ->withClubMember($this->club);
            }])
            ->orderBy('index')
            ->get();
    }

    public function addEntrant(): void
    {
        $currentEntrantPlayerIds = $this->getEntrantPlayerIds()->all();

        $this->validate([
            'selectedPlayer.id'   => ['required', 'integer', Rule::notIn($currentEntrantPlayerIds)], // prevents empty not_in edge case
            'selectedPlayer.rank' => ['nullable', 'integer', 'min:1'],
        ], [
            'selectedPlayer.id.required' => 'Please select a member.',
            'selectedPlayer.id.not_in'   => 'That player is already in the list.',
        ]);

        $count = $this->entrants->count();
        $rank  = $this->selectedPlayer['rank'];
        $targetIndex = ($rank === null || $rank === '')
            ? $count // bottom
            : max(0, min($count, (int) $rank - 1)); // clamp into [0, count]

        DB::transaction(function () use ($targetIndex): void {

            $entrant = $this->session->entrants()->create([
                'player_id' => (int) $this->selectedPlayer['id'],
                'index'     => 9_999_999, // ensures move() always shifts correctly
            ]);

            // Reset form state early to avoid stale UI.
            $this->selectedPlayer['id'] = null;
            $this->selectedPlayer['rank'] = null;

            $entrant->move($targetIndex);
        });

        // Refresh the public entrants property
        $this->entrants = $this->getEntrants();

        Flux::modals()->close('add-entrant');

        Flux::toast(
            variant: 'success',
            text: 'Entrant added.'
        );
    }

    public function removeEntrant(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entrant = $this->entrants->findOrFail($id);

            // Remove the contestant
            $contestant = Contestant::where('player_id', $entrant->player_id)
                ->whereHas('division', function ($query) {
                    $query->where('league_session_id', $this->session->id);
                })
                ->first();

            if ($contestant) {
                $contestant->forceDelete();
            }

            $entrant->delete();

            $this->entrants = $this->getEntrants();

            $this->resetErrorBag();
        });

        Flux::toast(
            variant: 'success',
            text: 'Entrant removed.'
        );
    }

    public function restore(): void
    {
        DB::transaction(function (): void {
            $contestants = Contestant::whereHas('division', function ($query) {
                    $query->where('league_session_id', $this->session->id);
                });

            $contestants->forceDelete();

            $this->session->entrants()->forceDelete();

            if ($this->league->sessions()->count() > 1) {
                $previousSession = $this->session->previous();

                $previousSession->contestants()->orderBy('overall_rank', 'asc')->get()->each(function ($contestant) {
                    $this->session->entrants()->create([
                        'player_id' => $contestant->player_id,
                        'index' => $contestant->overall_rank - 1,
                    ]);
                });

                Flux::toast(
                    variant: 'success',
                    text: 'Entrants reset.'
                );
            } else {
                Flux::toast(
                    variant: 'success',
                    text: 'Entrants deleted.'
                );
            }

            $this->entrants = $this->session->entrants()->orderBy('index')->get();

            $this->dispatch('remove-entrant');
        });

        Flux::modal('reset-entrants')->close();
    }

    #[Renderless]
    public function sortItem(int $item, int $position): void
    {
        $entrant = $this->entrants->findOrFail($item);
        $entrant->move($position);

        $this->resetErrorBag();

        $this->entrants = $this->session->entrants()->orderBy('index')->get();
    }

    #[Computed]
    public function canAddEntrants(): bool
    {
        return $this->availableMembers()->count() > 0;
    }

    protected function getEntrantPlayerIds(): Collection
    {
        return $this->entrants->pluck('player_id')->values();
    }

    #[Computed]
    public function availableMembers(): Collection
    {
        return $members = $this->club->members()
            ->whereNotIn('members.player_id', $this->getEntrantPlayerIds())
            ->orderByName()
            ->get();
    }

    #[Computed]
    public function missingFromCurrent(): Collection
    {
        $previous = $this->session->previous();
        if (! $previous) return collect();

        $prevIds = $previous->entrants()->pluck('player_id')->filter();
        $currIds = $this->getEntrantPlayerIds(); // you already memoize this

        $missingIds = $prevIds->diff($currIds)->values();

        return Player::withHasUser()
            ->whereIn('id', $missingIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function newInCurrent(): Collection
    {
        $previous = $this->session->previous();
        if (! $previous) {
            return collect();
        }

        // 1) Previous and current ENTRANT PLAYER IDs
        $prevPlayerIds = $previous->entrants()->pluck('player_id')->all();      // players in previous session
        $currPlayerIds = $this->getEntrantPlayerIds()->all();                   // players in current session

        // 2) New players in current session (by player_id)
        $newPlayerIds = array_values(array_diff($currPlayerIds, $prevPlayerIds));

        if (empty($newPlayerIds)) {
            return collect();
        }

        // 3) Fetch members of this club whose player_id is in that new set
        //    and 4) order by PLAYER name
        return $this->club->members()->withTrashed()
            ->whereIn('members.player_id', $newPlayerIds)
            ->orderByName()
            ->get();
    }

    #[Computed]
    public function originalPlayerIds()
    {
        return $this->initialEntrants->pluck('player_id');
    }

    #[Computed]
    public function hasClubMembers(): bool
    {
        return $this->club->members()->exists(); // why: cheaper than count()>0
    }

    public function with(): array
    {
        return [
            'hasPreviousSession'     => $this->session->previous(),
            'entrantCount'           => $this->entrants->count(),

        ];
    }
};
?>

<flux:card class="relative !bg-stone-50">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading size="lg" variant="strong">Seedings ({{ $entrantCount }})</flux:heading>
            <flux:button
                x-on:click="$flux.modal('add-entrant').show();"
                variant="primary"
                icon="plus"
                x-bind:disabled="{{ json_encode(! $this->canAddEntrants) }}"
            >
                Entrant
            </flux:button>

            @teleport('body')
                <flux:modal name="add-entrant" class="modal" x-on:close="$js.openEntrantModal;">
                    <form wire:submit="addEntrant">
                        <x-modals.content>
                            <x-slot:heading>Add Entrant</x-slot:heading>
                            <div class="sm:flex sm:items-center space-y-6 sm:space-y-0">
                                <div class="flex-1">
                                    <flux:field>
                                        <flux:label>Entrant</flux:label>
                                        <flux:select
                                            x-ref="entrant-error-border"
                                            id="select-member"
                                            type="number"
                                            variant="listbox"
                                            wire:model="selectedPlayer.id"
                                            searchable
                                            clearable
                                            placeholder="{{ __('Select member...') }}"
                                        >
                                            @foreach ($this->availableMembers as $member)
                                                <flux:select.option value="{{ $member->id }}" wire:key="member-{{ $member->id }}" class="!space-x-1">
                                                    <x-generic.entrant-tile :player="$member->player" />
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                </div>
                                <div
                                    @class([
                                        "hidden sm:block sm:h-10 sm:w-3",
                                        'sm:hidden' => $entrants->isEmpty()
                                    ])
                                >
                                    &nbsp;
                                </div>
                                <div
                                    @class([
                                        'w-28 sm:w-26 flex-none' => ! $entrants->isEmpty(),
                                        'hidden' => $entrants->isEmpty()
                                    ])
                                >
                                    <flux:field>
                                        <flux:label>Seed</flux:label>
                                        <flux:select wire:model="selectedPlayer.rank" placeholder="Seed">
                                            <flux:select.option value="">Bottom</flux:select.option>
                                            @foreach (range(1, $entrantCount) as $i)
                                                <flux:select.option value="{{ $i }}" wire:key="rank-{{ $i }}">{{ $i }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                </div>
                            </div>
                            <flux:error
                                x-ref="entrant-error-message"
                                name="selectedPlayer.id"
                            />
                            <x-slot:buttons>
                                <flux:button
                                    x-data="{
                                        selectedId: $wire.entangle('selectedPlayer.id')
                                    }"
                                    type="submit"
                                    variant="primary"
                                >
                                    {{ __('Add') }}
                                </flux:button>
                            </x-slot:buttons>
                        </x-modals.content>
                    </form>
                </flux:modal>
            @endteleport
        </div>

        @if ($this->canAddEntrants)
        @else
            @if (! $this->hasClubMembers)
                <flux:callout icon="exclamation-circle" text="You must first create some members before you can add entrants." />
            @else
                <flux:callout icon="exclamation-circle" heading="All members have been added" text="You must create more members before you can add more entrants." />
            @endif
        @endif

        @php
            $hasRemoved = count($this->missingFromCurrent) > 0;
            $hasNew = count($this->newInCurrent) > 0;
        @endphp
        @if ($hasRemoved || $hasNew)

            <div class="border-t border-b py-6">
                <flux:accordion class="!space-y-1.5">
                    @if ($hasRemoved)
                        <flux:accordion.item>
                            <flux:accordion.heading
                                @class([
                                    '!mb-1.5' => count($this->newInCurrent) > 0
                                ])
                            >
                                <flux:heading class="text-zinc-500 font-normal">Removed ({{ count($this->missingFromCurrent) }})</flux:heading>
                            </flux:accordion.heading>

                            <flux:accordion.content class="space-y-4 mt-2 mb-1">
                                <flux:text>Members that competed in the previous session.</flux:text>
                                <flux:table>
                                    <flux:table.rows>
                                        @foreach ($this->missingFromCurrent as $player)
                                            <flux:table.row wire:key="missing-from-current-{{ $player->id }}">
                                                <flux:table.cell class="!px-0 !py-1.5 text-xs !border-0">
                                                    -
                                                    <x-generic.entrant-tile :$player />
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    @endif

                    @if ($hasNew)
                        <flux:accordion.item>
                            <flux:accordion.heading>
                                <flux:heading class="flex items-center text-zinc-500 font-normal">
                                    New ({{ count($this->newInCurrent) }})
                                    <flux:icon.sparkles
                                        variant="micro"
                                        class="text-indigo-500 mx-1"
                                    />
                                </flux:heading>
                            </flux:accordion.heading>

                            <flux:accordion.content class="space-y-4 mt-2 mb-1">
                                <flux:text>New entrants that didn't compete in the previous session.</flux:text>
                                <flux:table>
                                    <flux:table.rows>
                                        @foreach ($this->newInCurrent as $entrant)
                                            <flux:table.row wire:key="new-in-current-{{ $entrant->id }}">
                                                <flux:table.cell class="!px-0 !py-1.5 text-xs !border-0">
                                                    -
                                                    <x-generic.entrant-tile :player="$entrant->player" />
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    @endif
                </flux:accordion>
            </div>

        @endif
    </div>

    @if ($entrants->count() > 0)
        <div class="space-y-6">
            <div class="flex items-center justify-between mt-6">
                <flux:text></flux:text>
                <flux:modal.trigger name="reset-entrants">
                    @if ($hasPreviousSession)
                        <flux:button
                            variant="danger"
                        >
                            Reset
                        </flux:button>
                    @else
                        <flux:button
                            variant="danger"
                        >
                            Delete
                        </flux:button>
                    @endif
                </flux:modal.trigger>

                @teleport('body')
                    <flux:modal name="reset-entrants" class="modal">
                        <form wire:submit="restore">
                            <x-modals.content>
                                @if ($hasPreviousSession)
                                    <x-slot:heading>
                                        {{ __('Reset Seedings') }}
                                    </x-slot:heading>
                                    <flux:text>Seedings will revert back to the 'Initial Seedings'.</flux:text>
                                    <flux:text>All entrants will be removed from the structure.</flux:text>
                                    <x-slot:buttons>
                                        <flux:button type="submit" variant="danger">Reset</flux:button>
                                    </x-slot:buttons>
                                @else
                                    <x-slot:heading>
                                        {{ __('Delete Entrants') }}
                                    </x-slot:heading>
                                    <flux:text>All entrants will be deleted and removed from the structure.</flux:text>
                                    <x-slot:buttons>
                                        <flux:button type="submit" variant="danger">Clear</flux:button>
                                    </x-slot:buttons>
                                @endif
                            </x-modals.content>
                        </form>
                    </flux:modal>
                @endteleport
            </div>

            <div
                wire:sort="sortItem"
                class="space-y-1"
                style="counter-reset: seed-counter 0;"
            >
                {{-- Use simpler wire:key --}}
                @foreach ($entrants as $entrant)
                    <div
                        wire:sort:item="{{ $entrant->id }}"
                        class="border rounded-md shadow-xs bg-white p-2 flex items-center gap-2 justify-between"
                        style="counter-increment: seed-counter;">

                        {{-- Precompute values to avoid repeated function calls --}}
                        @php
                            $isNew = $hasPreviousSession && !$this->originalPlayerIds->contains($entrant->player_id);
                        @endphp

                        <div class="flex items-center gap-2">
                            <flux:button
                                size="xs"
                                variant="subtle"
                                icon="grip"
                                icon:variant="micro"
                                class="!shrink-0"
                                wire:sort:handle
                            />
                            <div class="w-8 text-center text-xs text-zinc-500 before:content-['#'counter(seed-counter)]"></div>
                            <x-generic.entrant-tile :player="$entrant->player" />
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($isNew)
                                <flux:icon.sparkles variant="micro" class="size-4 text-indigo-500" />
                            @endif
                            <flux:button
                                wire:click="removeEntrant({{ $entrant->id }})"
                                variant="subtle"
                                icon="trash"
                                icon:variant="outline"
                                size="xs"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <div wire:loading wire:target="sortItem, restore, removeEntrant" class="absolute inset-0 z-50 bg-white -my-3.5 opacity-50"></div>

</flux:card>

<script>
    this.$js.openEntrantModal = () => {
        // Declare the variable with const or let
        const el = document.querySelector('[x-ref="entrant-error-border"]');
        document.querySelector('[x-ref="entrant-error-message"]').classList.add('hidden');
        console.log(el)

        if (el) {
            el.classList.remove('border-red-500');

            const button = el.children[0];
            if (button) {
                button.classList.remove('border-red-500');
            }
        }

        this.selectedPlayer = {
            id: null,
            rank: null
        }
    }
</script>

<style>
.seed-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    counter-reset: seed-counter;
}

.seed-item {
    counter-increment: seed-counter;
}

.seed-number::before {
    content: '#' counter(seed-counter);
    font-weight: 500;
    color: #374151; /* gray-700 */
}
</style>