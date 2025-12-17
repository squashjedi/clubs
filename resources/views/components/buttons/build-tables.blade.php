<?php

use App\Models\Club;
use App\Models\Tier;
use App\Models\League;
use App\Models\Session;
use Livewire\Component;
use App\Models\Division;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public function buildTables()
    {
        DB::transaction(function () {
            $this->saveTemplate();

            $this->session->update([
                'built_at' => now(),
            ]);
        });

        return $this->redirectRoute('club.admin.leagues.sessions.tables', [ 'club' => $this->club, 'league' => $this->league, 'session' => $this->session ], navigate: true);
    }

    protected function saveTemplate(): void
    {
        $template = $this->session->tiers()
            ->with(['divisions' => fn ($q) => $q->orderBy('index')])
            ->orderBy('index')
            ->get()
            ->map(function (Tier $tier) {
                return [
                    'name' => $tier->name,
                    'divisions' => $tier->divisions->map(function (Division $d) {
                        return [
                            'contestant_count' => (int) $d->contestant_count,
                            'promote_count'    => (int) $d->promote_count,
                            'relegate_count'   => (int) $d->relegate_count,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

        $this->league->update([
            'template' => $template,
        ]);
    }

}; ?>

<flux:button
    wire:click="buildTables"
    variant="primary"
    color="green"
    icon="arrow-right"
>
    {{ __('Tables') }}
</flux:button>