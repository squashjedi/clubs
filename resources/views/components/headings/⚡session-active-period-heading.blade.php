<?php

use Flux\Flux;
use Flux\DateRange;
use App\Models\Club;
use App\Models\League;
use App\Models\Result;
use App\Models\Session;
use Livewire\Component;
use Carbon\CarbonInterface;
use Livewire\Attributes\On;
use Carbon\CarbonImmutable as Carbon;
use Livewire\Attributes\Validate;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public ?string $route = null;

    public bool $editing = false;

    #[Validate('required')]
    public DateRange $range;

    public DateRange $initialRange;

    public function mount()
    {
        $this->route = request()->fullUrl();
        $tz = $this->session->timezone;

        $this->range = new DateRange(
            $this->session->starts_at->timezone($tz)->startOfDay()->format('Y-m-d'),
            $this->session->ends_at->timezone($tz)->startOfDay()->format('Y-m-d'),
        );

        $this->initialRange = $this->range;
    }

    #[On('update-status')]
    public function updateStatus()
    { }

    public function minStartDateForPicker(): ?string
    {
        $prev = $this->league->sessions()->where('id', '<', $this->session->id)->orderBy('id', 'desc')->first();

        if (! $prev) return null;

        $tz = $this->session->timezone ?? $this->league->club->timezone ?? 'UTC';

        // If your previous session used an EXCLUSIVE end (next local midnight in UTC),
        // the next session may start ON the same local day:
        $exclusiveEnd = false; // <-- set true if you use exclusive ends

        $prevEndLocal = $prev->ends_at->timezone($tz);

        return $exclusiveEnd
            ? $prevEndLocal->toDateString()            // same local day is allowed
            : $prevEndLocal->addDay()->toDateString(); // require the day after (inclusive-end style)
    }

    public function save()
    {
        $tz = $this->session->timezone;

        // 1) Build new bounds (local → UTC). End is *exclusive* next midnight.
        $startUtc = Carbon::parse(Carbon::parse($this->range->start, 'UTC')->format('Y-m-d'), $tz)->startOfDay()->utc();
        $endUtc   = Carbon::parse(Carbon::parse($this->range->end, 'UTC')->format('Y-m-d'), $tz)->endOfDay()->utc();

        // 2) Any results outside [startUtc, endUtc)?
        $resultsExist = Result::query()
            ->where('league_session_id', $this->session->id)
            ->where(function($q) use ($startUtc, $endUtc) {
                $q->where('match_at', '<', $startUtc)
                ->orWhere('match_at', '>', $endUtc);
            })
            ->exists();

        if ($resultsExist) {
            $startLocal = $startUtc->timezone($tz)->format('j M Y H:i');
            $endLocal   = $endUtc->timezone($tz)->format('j M Y H:i'); // last minute inside window
            $this->addError('range', __("There are results outside of :start → :end. Adjust the period or delete those results.", [
                'start' => $startLocal, 'end' => $endLocal
            ]));
            return;
        }

        // 3) Safe to update the session window
        $this->session->update([
            'starts_at' => $startUtc,
            'ends_at'   => $endUtc,
        ]);

        $this->editing = false;

        $this->initialRange = new DateRange(
            $this->session->starts_at->timezone($tz)->startOfDay()->format('Y-m-d'),
            $this->session->ends_at->timezone($tz)->startOfDay()->format('Y-m-d'),
        );

        $this->dispatch('date-updated');

        Flux::toast(
            variant: "success",
            text: "Active Period updated."
        );
    }
}; ?>

<div class="w-full relative">
    <div class="sm:flex sm:items-center sm:justify-between space-y-3 sm:space-y-0">

        <div class="sm:flex sm:flex-row-reverse sm:items-center sm:gap-2">
            <div class="flex-1 sm:flex sm:flex-row-reverse sm:justify-end sm:items-center mb-2 sm:mb-0">
                <div x-show="!$wire.editing" class="flex items-center gap-2 min-h-10 mt-1 sm:mt-0">
                    <div class="flex items-center gap-2">
                        <flux:heading size="xl">{{ $session->active_period }}</flux:heading>
                        <div class="hidden sm:block my-1">
                            <x-tags.session-status-tag :$session />
                        </div>
                        @if (is_null($session->processed_at))
                            <flux:button @click="$wire.editing = true" icon="pencil-square" icon:variant="outline" variant="subtle" size="sm"></flux:button>
                        @endif
                    </div>
                </div>
                <div x-show="$wire.editing" class="min-h-10 max-w-xs mt-1 sm:mt-0" >
                    <flux:input.group x-cloak @click.outside="$wire.editing = false;$wire.range = $wire.initialRange;$js.closeActivePeriod">
                        @php
                            $tz = $session->timezone ?? $league->club->timezone ?? 'UTC';
                            $minDate = $this->minStartDateForPicker();
                        @endphp
                        <flux:date-picker
                            x-ref="error-border"
                            mode="range"
                            :min="$minDate"
                            wire:model="range"
                            class="flex-1 w-xs"
                        />
                        <flux:button wire:click="save" icon="check" variant="primary"></flux:button>
                    </flux:input.group>
                </div>
                <div class="block sm:hidden">
                    <x-tags.session-status-tag :$session />
                </div>
            </div>
            @if ($session->isBuilt())
                <flux:dropdown>
                    <flux:button
                        icon="ellipsis-vertical"
                    />

                    <flux:menu>
                        <flux:menu.item
                            href="{{ route('club.admin.leagues.sessions.tables', [$club, $league, $session]) }}"
                            :current="request()->routeIs('club.admin.leagues.sessions.tables') || request()->routeIs('club.admin.leagues.sessions.tables.*')"
                            wire:navigate
                        >
                            Tables
                        </flux:menu.item>

                        <flux:menu.separator />

                        <flux:menu.item href="{{ route('club.admin.leagues.sessions.competitors', [$club, $league, $session]) }}"
                            :current="request()->routeIs('club.admin.leagues.sessions.competitors')"
                            wire:navigate
                        >
                            Competitors
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif

        </div>
        @error('range')
            <div>
                <flux:error
                    x-ref="error"
                    :message="$message"
                />
            </div>
        @enderror
        @php
            $isLatestSession = $session->id === $league->latestSession()->first()->id;
        @endphp
        @if (! is_null($session->processed_at) && $isLatestSession)
            <div class="min-h-10">
                <flux:button
                    href="{{ route('club.admin.leagues.sessions.create', [$club, $league]) }}"
                    variant="primary"
                    icon="plus"
                    wire:navigate
                >
                    Session
                </flux:button>
            </div>
        @endif
    </div>
    <div.flex wire:loading class="absolute inset-0 bg-white opacity-50" />
</div>

<script>
    this.$js.closeActivePeriod = () => {
        document.querySelector('[x-ref="error"]').classList.add('hidden');

        datePickerEl = document.querySelector('[x-ref="error-border"]')
        datePickerEl.classList.remove('border-red-500')
        button = datePickerEl.children[0];
        if (button) {
            button.classList.remove('border-red-500');
        };
    }
</script>