<?php

use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public function mount()
    {
        if ($this->session->isBuilt()) {
            $this->redirectRoute('club.admin.leagues.sessions.tables', [ 'club' => $this->club, 'league' => $this->league, 'session' => $this->session ], navigate: true);
        }
    }

    public function with(): array
    {
        $previousSession = $this->session->previous();
        $initialEntrants = collect();

        if ($previousSession) {
            $initialEntrants = $previousSession->contestants()->withTrashed()
                ->orderBy('overall_rank', 'asc')
                ->with(['player' => fn ($q) => $q->withHasUser()])
                ->get();
        }

        return [
            'initialEntrants' => $initialEntrants,
            'initialEntrantCount' => $initialEntrants->count()
        ];
    }
}; ?>

<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [ $club ]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [ $club ]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.edit', [ $club, $league ]) }}" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions', [ $club, $league ]) }}" wire:navigate>Sessions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions.show', [ $club, $league, $session ]) }}" wire:navigate>{{ $session->active_period }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Entrants') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-containers.club-admin-session-builder :$club :$league :$session>
        <div class="grid md:grid-cols-16 gap-6">
            <div class="md:col-span-9">
                <livewire:generic.session-seeding-list lazy :$club :$league :$session :$initialEntrants />
            </div>
            @if ($initialEntrantCount > 0)
                <div class="md:col-span-7">
                    <flux:card
                        x-data="{ show: false}"
                        class="space-y-4"
                    >
                        <flux:accordion>
                            <flux:accordion.item>
                                <flux:accordion.heading>
                                    <flux:heading size="lg">Initial Seedings ({{ $initialEntrantCount }})</flux:heading>
                                </flux:accordion.heading>

                                <flux:accordion.content class="mt-2 space-y-4">
                                    <flux:text>Previous session final positions after promotions and relegations.</flux:text>
                                    <flux:table>
                                        <flux:table.rows>
                                            @foreach ($initialEntrants as $entrant)
                                                <flux:table.row>
                                                    <flux:table.cell class="!pl-0 !py-1.5 !w-0 text-xs !border-0">
                                                        <div class="text-right">#{{ $entrant->overall_rank }}</div>
                                                    </flux:table.cell>
                                                    <flux:table.cell class="!pr-0 !py-1.5 text-xs !border-0">
                                                        <x-generic.entrant-tile :player="$entrant->player" class="inline-block" />
                                                    </flux:table.cell>
                                                </flux:table.row>
                                            @endforeach
                                        </flux:table.rows>
                                    </flux:table>
                                </flux:accordion.content>
                            </flux:accordion.item>
                        </flux:accordion>
                    </flux:card>
                </div>
            @endif
        </div>
    </x-containers.club-admin-session>
</div>