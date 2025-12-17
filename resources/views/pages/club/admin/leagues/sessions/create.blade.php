<?php

use Flux\Flux;
use Flux\DateRange;
use App\Models\Club;
use App\Models\League;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Carbon\CarbonImmutable as Carbon;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

    public League $league;

    #[Validate('required')]
    public DateRange $range;

    public function save()
    {
        $this->validate();

        DB::transaction(function () {
            $tz = $this->club->timezone;

            $session = $this->league->sessions()->create([
                'timezone' => $this->club->timezone,
                'starts_at' => Carbon::parse(Carbon::parse($this->range->start, 'UTC')->format('Y-m-d'), $tz)->startOfDay()->utc(),
                'ends_at' => Carbon::parse(Carbon::parse($this->range->end, 'UTC')->format('Y-m-d'), $tz)->endOfDay()->utc(),
            ]);

            $previousSession = $session->previous();

            if ($previousSession) {
                $session->update([
                    'pts_win' => $previousSession->pts_win,
                    'pts_draw' => $previousSession->pts_draw,
                    'pts_per_set' => $previousSession->pts_per_set,
                    'pts_play' => $previousSession->pts_play,
                ]);

                $previousSession->contestants()->withTrashed()->orderBy('overall_rank', 'asc')->get()->each(function ($prevContestant) use ($session) {
                    $session->entrants()->create([
                        'player_id' => $prevContestant->player_id,
                        'index' => $prevContestant->overall_rank - 1,
                    ]);
                });
            }

            Flux::toast(
                variant: 'success',
                text: "Session created."
            );

            $this->redirectRoute('club.admin.leagues.sessions.show', ['club' => $this->club, 'league' => $this->league, 'session' => $session ], navigate: true);
        });
    }
}; ?>

<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('club.admin', [$club]) }}" wire:navigate>Dashboard</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues', [$club]) }}" wire:navigate>{{ __("Leagues") }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.edit', [$club, $league]) }}" wire:navigate>{{ $league->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions', [$club, $league]) }}" wire:navigate>Sessions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('club.admin.leagues.sessions.create', [$club, $league]) }}" wire:navigate>Create</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <x-headings.league-with-sub-heading :$club :$league />

    <x-headings.page-heading>
        @if (! $league->sessions()->exists())
            Create First Session
        @else
            Create New Session
        @endif
    </x-headings.page-heading>

    <form wire:submit="save" class="space-y-6">
        <flux:date-picker
            wire:model="range"
            label="{{ __('Active Period') }}"
            min="{{ $league->latestSession?->ends_at->addDays(1)->format('Y-m-d') }}"
            mode="range"
            class="!max-w-xs"
        />

        <div class="flex">
            <flux:button type="submit" variant="primary">Create</flux:button>
        </div>
    </form>
</div>
