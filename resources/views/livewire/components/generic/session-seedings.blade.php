<?php
// resources/views/livewire/entrants.volt.php

declare(strict_types=1);

use Flux\Flux;
use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use App\Models\Contestant;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;

new class extends Component
{
    /** Context */
    public Club $club;

    public League $league;

    public Session $session;

    public ?Collection $initialEntrants;

    public bool $didChange = false;

    /** Bulk add: selected member IDs. */
    public ?array $selectedMembers = [
        'ids' => null, // kept nullable for UI compatibility
    ];

    /** Single add: selected member ID and optional rank (1-based). */
    #[Validate('required', message: 'A member is required.')]
    public ?array $selectedMember = [
        'id' => null,
        'rank' => null,
    ];

    /** Per-request memoization to avoid repeated queries. */
    private ?Collection $cacheCurrent = null;        // Collection<Entrant>
    private ?Collection $cacheCurrentIds = null;     // Collection<int>
    private ?Collection $cacheAvailableMembers = null; // Collection<Member>
    private ?bool $cacheHasPrevious = null;          // bool

    /** Eager load cheap, frequently used relations once. */
    public function mount(): void
    {
        // why: prevent repeated lazy loads for names in the template
        $this->club->loadMissing(['members:id,club_id,first_name,last_name']);
    }

    protected function query()
    {
        return $this->session->entrants()
            ->with(['member' => fn ($q) => $q->withTrashed()]);
    }

    /** entrants ordered by index (memoized). */
    protected function getEntrants(): Collection
    {
        if ($this->cacheCurrent === null) {
            $this->cacheCurrent = $this->query()->orderBy('index')->get();
        }
        return $this->cacheCurrent;
    }

    /** Entrant member IDs (memoized). */
    protected function getEntrantIds(): Collection
    {
        if ($this->cacheCurrentIds === null) {
            $this->cacheCurrentIds = $this->getEntrants()->pluck('member_id')->values();
        }
        return $this->cacheCurrentIds;
    }

    /** Used by Alpine/hx updates. */
    public function updated(): void
    {
        $this->dispatch('structure');
    }

    /**
     * Reset current entrant list.
     * - If no previous session: clear the list.
     * - Else: mirror previous session order into current (before).
     */
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
                        'member_id' => $contestant->member_id,
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
        });

        $this->didChange = true;

        Flux::modal('reset-entrants')->close();
        // Invalidate caches after mutation
        $this->resetCaches();
    }

    #[Computed]
    public function allocatedMemberIdSet(): array
    {
        // All member_ids currently placed in divisions for this session
        $ids = $this->session->contestants()
            ->pluck('member_id')
            ->filter()                    // drop nulls
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        // O(1) membership test
        return array_fill_keys($ids, true);
    }

    /** Add a single member at a given 1-based rank (or bottom). */
    public function addEntrant(): void
    {
        $currentIds = $this->getEntrantIds()->all();

        $this->validate([
            'selectedMember.id'   => ['required', 'integer', Rule::notIn($currentIds)], // prevents empty not_in edge case
            'selectedMember.rank' => ['nullable', 'integer', 'min:1'],
        ], [
            'selectedMember.id.required' => 'Please select a member.',
            'selectedMember.id.not_in'   => 'That member is already in the list.',
        ]);

        $count = $this->getEntrants()->count();
        $rank  = $this->selectedMember['rank'];
        $targetIndex = ($rank === null || $rank === '')
            ? $count // bottom
            : max(0, min($count, (int) $rank - 1)); // clamp into [0, count]

        DB::transaction(function () use ($targetIndex): void {
            $entrant = $this->query()->create([
                'member_id' => (int) $this->selectedMember['id'],
                'index'     => 9_999_999, // ensures move() always shifts correctly
            ]);

            // Reset form state early to avoid stale UI.
            $this->selectedMember['id'] = null;
            $this->selectedMember['rank'] = null;

            $entrant->move($targetIndex);
        });

        // Optionally close the add modal after a successful single add.

        $this->didChange = true;

        $this->resetCaches();

        Flux::modals()->close('add-entrant');

        Flux::toast(
            variant: 'success',
            text: 'Entrant added.'
        );
    }

    /** Remove entrant by row id. */
    public function remove(int $id): void
    {
        $entrant = $this->query()->findOrFail($id);

        DB::transaction(function () use ($entrant) {
            // Remove the contestant with the given member_id in this league_session
            $contestant = Contestant::where('member_id', $entrant->member_id)
                ->whereHas('division', function ($query) {
                    $query->where('league_session_id', $this->session->id);
                })
                ->first();

            if ($contestant) {
                $contestant->forceDelete();
            }

            $entrant->delete();

            $this->resetErrorBag();
        });

        $this->didChange = true;
        $this->resetCaches();

        Flux::toast(
            variant: 'success',
            text: 'Entrant deleted.'
        );
    }

    /** Drag/sort handler: move entrant to zero-based index. */
    public function sort(int $id, int $index): void
    {
        $entrant = $this->query()->findOrFail($id);
        $entrant->move($index);

        $this->resetErrorBag();
        $this->didChange = true;
        $this->resetCaches();
    }

    /** Whether the club has any members at all. */
    #[Computed]
    public function hasClubMembers(): bool
    {
        return $this->club->members()->exists(); // why: cheaper than count()>0
    }

    /** UI: true if we can add players (any available members left). */
    #[Computed]
    public function canAddEntrants(): bool
    {
        return $this->availableMembers()->count() > 0;
    }

    /** Members of the club not currently competing (memoized). */
    public function availableMembers(): Collection
    {
        if ($this->cacheAvailableMembers === null) {
            $this->cacheAvailableMembers = $this->club->members()
                ->whereNotIn('id', $this->getEntrantIds())
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }
        return $this->cacheAvailableMembers;
    }

    #[Computed]
    public function originalMemberIds()
    {
        return $this->initialEntrants->pluck('member_id');
    }

    /** Data exposed to the template. */
    public function with(): array
    {
        // why: pass concrete Collections & booleans to avoid repeated DB calls in the view
        $entrants = $this->getEntrants();

        return [
            'hasPreviousSession'     => $this->session->previous(),
            'entrantCount'           => $entrants->count(),
            'entrants'               => $entrants,
            'availableMembers'       => $this->availableMembers(),
        ];
    }

    #[Computed]
    public function missingFromCurrent(): \Illuminate\Support\Collection
    {
        $previous = $this->session->previous();
        if (! $previous) return collect();

        $prevIds = $previous->entrants()->pluck('member_id')->filter();
        $currIds = $this->getEntrantIds(); // you already memoize this

        $missingIds = $prevIds->diff($currIds)->values();

        return \App\Models\Member::withTrashed()
            ->whereIn('id', $missingIds)
            ->orderBy('last_name')->orderBy('first_name')
            ->get();
    }
    #[Computed]
    public function newInCurrent(): Collection
    {
        $previous = $this->session->previous();
        if (! $previous) return collect();

        // Get previous and current entrant member IDs as arrays (no models loaded)
        $prevIds = $previous->entrants()->pluck('member_id')->all();
        $currIds = $this->getEntrantIds()->all();

        // Compute new IDs using array_diff for speed (all ints)
        $newIds = array_values(array_diff($currIds, $prevIds));

        if (empty($newIds)) {
            return collect();
        }

        // Fetch only needed members, let DB do the ordering
        return $this->club->members()->withTrashed()
            ->whereIn('id', $newIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function hasPreviousSession()
    {
        return $this->league->sessions()->count() > 1;
    }

    /** Invalidate per-request caches after mutations. */
    private function resetCaches(): void
    {
        $this->cacheCurrent = null;
        $this->cachePrev = null;
        $this->cacheCurrentIds = null;
        $this->cachePrevIds = null;
        $this->cacheAvailableMembers = null;
        $this->cacheHasPrevious = null;
        // also clear stashed previous model
        if (method_exists($this->session, 'relationLoaded') && $this->session->relationLoaded('cached_previous_model')) {
            $this->session->unsetRelation('cached_previous_model');
        }
    }
}; ?>

<flux:card class="relative !bg-stone-50">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading size="lg" variant="strong">Seedings ({{ $entrantCount }})</flux:heading>
            <flux:modal.trigger name="add-entrant">
                <flux:button
                    x-on:click="$js.openModal"
                    variant="primary"
                    icon="plus"
                    x-bind:disabled="{{ ! $this->canAddEntrants }}"
                >
                    Entrant
                </flux:button>
            </flux:modal.trigger>

            @teleport('body')
                <flux:modal name="add-entrant" class="modal">
                    <form wire:submit="addEntrant">
                        <x-modals.content>
                            <x-slot:heading>Add Entrant</x-slot:heading>
                            <div class="sm:flex sm:items-center space-y-6 sm:space-y-0">
                                <div class="flex-1">
                                    <flux:field>
                                        <flux:label>Member</flux:label>
                                        <flux:select
                                            id="select-member"
                                            type="number"
                                            variant="listbox"
                                            wire:model="selectedMember.id"
                                            searchable
                                            clearable
                                            placeholder="{{ __('Select member...') }}"
                                        >
                                            @foreach ($availableMembers as $member)
                                                <flux:select.option value="{{ $member->id }}" wire:key="member-{{ $member->id }}"><x-generic.member :$club :$member :isLink="false" class="inline-block" /></flux:select.option>
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
                                        <flux:select wire:model="selectedMember.rank" placeholder="Seed">
                                            <flux:select.option value="">Bottom</flux:select.option>
                                            @foreach (range(1, $entrantCount) as $i)
                                                <flux:select.option value="{{ $i }}" wire:key="rank-{{ $i }}">{{ $i }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                </div>
                            </div>
                            <x-slot:buttons>
                                <flux:button
                                    x-data="{
                                        selectedId: $wire.entangle('selectedMember.id')
                                    }"
                                    type="submit"
                                    variant="primary"
                                    x-bind:disabled="!selectedId"
                                    :loading="false"
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

        @if (count($this->newInCurrent) > 0 || count($this->missingFromCurrent) > 0)
            <div class="border-t border-b py-6">
                <flux:accordion class="!space-y-1.5">
                    @if (count($this->missingFromCurrent) > 0)
                        <flux:accordion.item>
                            <flux:accordion.heading
                                @class([
                                    '!mb-1.5' => count($this->newInCurrent) > 0
                                ])
                            >
                                <flux:heading>Removed ({{ count($this->missingFromCurrent) }})</flux:heading>
                            </flux:accordion.heading>

                            <flux:accordion.content class="space-y-4 mt-2 mb-1">
                                <flux:text>Members that competed in the previous session.</flux:text>
                                <flux:table>
                                    <flux:table.rows>
                                        @foreach ($this->missingFromCurrent as $member)
                                            <flux:table.row>
                                                <flux:table.cell class="!px-0 !py-1.5 text-xs !border-0">
                                                    -
                                                    <x-generic.member :$club :$member class="ml-1 inline-block" />
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    @endif

                    @if (count($this->newInCurrent) > 0)
                        <flux:accordion.item>
                            <flux:accordion.heading>
                                <flux:heading class="flex items-center">
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
                                        @foreach ($this->newInCurrent as $member)
                                            <flux:table.row>
                                                <flux:table.cell class="!px-0 !py-1.5 text-xs !border-0">
                                                    -
                                                    <x-generic.member :$club :$member class="ml-1 inline-block" />
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

        @if ($entrantCount > 0 || $hasPreviousSession)
            <div class="space-y-6">
                <div class="flex items-center justify-between mt-6">
                    <flux:text>Total: {{ $entrantCount }}</flux:text>
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

                <div x-data="{ showErrors: true }" class="space-y-2">
                    <div x-sort="$wire.sort($item, $position)" class="space-y-1">
                        @foreach ($entrants as $index => $entrant)
                            @php
                                $isAllocated = isset($this->allocatedMemberIdSet[$entrant->member_id]);
                            @endphp

                            <div
                                x-data="{ entrantId: {{ $entrant->id }} }"
                                x-sort:item="{{ $entrant->id }}"
                                wire:key="entrant-{{ $entrant->id }}"
                                class=""
                            >
                                <x-generic.entrant-tile :$entrant :$index :hasPreviousSession="$this->hasPreviousSession" :$isAllocated :originalMemberIds="$this->originalMemberIds" :editable="true" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>
    <div wire:loading class="absolute inset-0 z-50 bg-white -my-3.5 opacity-50"></div>
</flux:card>

@script
<script>
    $js('hideErrors', () => {
        document.querySelectorAll('[x-ref="error"]').forEach(el => {
            el.classList.add('hidden');
        });
        document.querySelectorAll('[x-ref="error-border"]').forEach(el => {
            el.classList.remove('border-red-500');
            button = el.children[0];
            if (button) {
                button.classList.remove('border-red-500');
            }
        });
    })
</script>
@endscript
