<?php

use App\Models\Club;
use App\Models\Session;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component
{
    public Club $club;

    public Session $session;

    public array $tiers;

    #[Computed]
    public function currentCompetitorCount(): int
    {
        return $this->session->competitors()->before()->count();
    }

    #[On('update-competitor-data')]
    public function updatedSession()
    {
    }

    #[Computed]
    public function initialIds()
    {
        return $this->session->previous()?->competitors()->after()->pluck('member_id') ?? collect([]);
    }

    #[Computed]
    public function currentIds()
    {
        return $this->session?->competitors()->before()->pluck('member_id') ?? collect([]);
        // return collect($this->tiers)
        //     ->flatMap(fn ($tier) => $tier['divisions'] ?? [])
        //     ->flatMap(fn ($division) => $division['contestants'] ?? [])
        //     ->pluck('member_id');
    }

    #[Computed]
    public function newCompetitorCount(): int
    {
        return $this->currentIds->diff($this->initialIds)->count();
    }

    #[Computed]
    public function removedCompetitorCount(): int
    {
        return $this->initialIds->diff($this->currentIds)->count();
    }

    #[Computed]
    public function isStructureDifferentFromSeedings(): bool
    {
        // 1. Expected order from seedings
        $seedingIds = $this->session->competitors()
            ->before()
            ->orderBy('index')
            ->pluck('member_id')
            ->toArray();

        // 2. Actual structure order: row-first across divisions in each tier
        $structuredIds = [];

        foreach ($this->tiers as $tier) {
            $divisions = $tier['divisions'] ?? [];

            // Max contestants in any division in this tier
            $maxRows = collect($divisions)
                ->map(fn($d) => count($d['contestants'] ?? []))
                ->max();

            // Row-wise extraction
            for ($i = 0; $i < $maxRows; $i++) {
                foreach ($divisions as $division) {
                    if (isset($division['contestants'][$i])) {
                        $structuredIds[] = $division['contestants'][$i]['member_id'];
                    }
                }
            }
        }

        return $seedingIds !== $structuredIds;
    }
}; ?>

<flux:card class="space-y-3">
    @if ($this->isStructureDifferentFromSeedings)
        <div>Is different</div>
    @endif
    <div class="flex items-center gap-2">
        <flux:heading size="lg" variant="strong">{{ __('Competitors') }}</flux:heading>
        <flux:modal.trigger name="competitors">
            <flux:button variant="subtle" icon="square-pen" size="xs" />
        </flux:modal.trigger>

        <flux:modal name="competitors" variant="flyout">
            <livewire:components.generic.session-seedings lazy :$club :$session />
        </flux:modal>
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div class="space-y-1">
            <div>Total</div>
            <div class="font-semibold">{{ $this->currentCompetitorCount }}</div>
        </div>
        <div class="space-y-1">
            <div>New</div>
            <div class="font-semibold">{{ $this->newCompetitorCount }}</div>
        </div>
        <div class="space-y-1">
            <div>Removed</div>
            <div class="font-semibold">{{ $this->removedCompetitorCount }}</div>
        </div>
    </div>
</flux:card>