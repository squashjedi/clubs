<?php

use App\Models\Club;
use App\Models\Session;
use Livewire\Component;

new class extends Component
{
    public Club $club;

    public function with(): array
    {
        $now = now();

        return [
            'leaguesNoSession' => $this->club->leagues()
                ->whereDoesntHave('sessions')
                ->orderByDesc('id')
                ->get(),
            'leaguesNotPublished' => $this->club->leagues()
                ->with(['latestSession' => function ($q) {
                    $q->unpublished()
                        ->latest('starts_at');
                }])
                ->withMax(['sessions as not_published_starts_at' => function ($q) {
                    $q->unpublished();
                }], 'starts_at')
                ->havingNotNull('not_published_starts_at')
                ->orderBy('not_published_starts_at')
                ->get(),
            'leaguesStartingSoon' => $this->club->leagues()
                ->with(['latestSession' => function ($q) {
                    $q->startingSoon()
                        ->published()
                        ->latest('starts_at');
                }])
                ->withMax(['sessions as starting_soon_starts_at' => function ($q) {
                    $q->startingSoon()
                        ->published();
                }], 'starts_at')
                ->havingNotNull('starting_soon_starts_at')
                ->orderBy('starting_soon_starts_at')
                ->get(),
            'leaguesInProgress' => $this->club->leagues()
                ->with(['latestSession' => function ($q) {
                    $q->inProgress()
                        ->published()
                        ->latest('ends_at');
                }])
                ->withMax(['sessions as in_progress_ends_at' => function ($q) {
                    $q->inProgress()
                        ->published();
                }], 'ends_at')
                ->havingNotNull('in_progress_ends_at')
                ->orderBy('in_progress_ends_at')
                ->get(),
            'leaguesWaitingToBeProcessed' => $this->club->leagues()
                ->with(['latestSession' => function ($q) {
                    $q->published()
                        ->ended()
                        ->notProcessed()
                        ->latest('ends_at');
                }])
                ->withMax(['sessions as waiting_to_be_processed_ends_at' => function ($q) {
                    $q->published()
                        ->ended()
                        ->notProcessed();
                }], 'ends_at')
                ->havingNotNull('waiting_to_be_processed_ends_at')
                ->orderBy('waiting_to_be_processed_ends_at')
                ->get(),
            'leaguesProcessed' => $this->club->leagues()
                ->with('latestSession')
                ->whereHas('latestSession', fn($q) => $q->processed())
                    ->orderBy(
                        Session::select('ends_at')
                            ->whereColumn('league_id', 'leagues.id')
                            ->latest()
                            ->limit(1)
                    )
                    ->get(),
            'leaguesArchived' => $this->club->leagues()
                ->onlyTrashed()
                ->orderByDesc('deleted_at')
                ->get(),
        ];
    }
};
?>

<div class="space-y-6 w-full">
    @php
        $leagues = [];
        $leagues['NoSessionCount'] = $leaguesNoSession->count();
        $leagues['NotPublishedCount'] = $leaguesNotPublished->count();
        $leagues['StartingSoonCount'] = $leaguesStartingSoon->count();
        $leagues['InProgressCount'] = $leaguesInProgress->count();
        $leagues['WaitingToBeProcessedCount'] = $leaguesWaitingToBeProcessed->count();
        $leagues['ProcessedCount'] = $leaguesProcessed->count();
        $leagues['ArchivedCount'] = $leaguesArchived->count();
        $leagueCount = array_sum($leagues);
    @endphp
    <flux:heading size="lg">Leagues</flux:heading>


    <div class="relative">
        <div class="absolute h-full inset-y-0 right-0 bg-gradient-to-l from-white to-transparent dark:from-zinc-900 dark:to-transparent"></div>
            <div class="overflow-x-scroll pb-2 h-full w-full">
                <div>
                    <flux:kanban>
                        @if ($leagues['NoSessionCount'] > 0)
                            <x-cards.league-status-kanban-card
                                name="Empty"
                                :leagueCount="$leagues['NoSessionCount']"
                                :$club
                                :leagues="$leaguesNoSession"
                                :hasLeagueSessions="false"
                                color="zinc"
                                info="No sessions."
                            />
                        @endif
                        @if ($leagues['NotPublishedCount'] > 0)
                            <x-cards.league-status-kanban-card
                                name="Drafts"
                                :leagueCount="$leagues['NotPublishedCount']"
                                :$club
                                :leagues="$leaguesNotPublished"
                                color="zinc"
                                info="Not live."
                            />
                        @endif
                        @if ($leagues['StartingSoonCount'] > 0)
                            <x-cards.league-status-kanban-card
                                name="Upcoming"
                                :leagueCount="$leagues['StartingSoonCount']"
                                :$club
                                :leagues="$leaguesStartingSoon"
                                color="amber"
                                :showTag="true"
                                info="Starting soon."
                            />
                        @endif
                        @if ($leagues['InProgressCount'] > 0)
                            <x-cards.league-status-kanban-card
                                name="Active"
                                :leagueCount="$leagues['InProgressCount']"
                                :$club
                                :leagues="$leaguesInProgress"
                                :showTag="true"
                                color="green"
                                info="In progress."
                            />
                        @endif

                        @if ($leagues['WaitingToBeProcessedCount'] > 0)
                            <x-cards.league-status-kanban-card
                                name="Pending"
                                :leagueCount="$leagues['WaitingToBeProcessedCount']"
                                :$club
                                :leagues="$leaguesWaitingToBeProcessed"
                                :expanded="true"
                                color="red"
                                info="Waiting to be processed."
                            />
                        @endif

                        @if ($leagues['ProcessedCount'] > 0)
                            <x-cards.league-status-kanban-card
                                name="Processed"
                                :leagueCount="$leagues['ProcessedCount']"
                                :$club
                                :leagues="$leaguesProcessed"
                                color="blue"
                                info="Processed and waiting for a new session."
                            />
                        @endif
                    </flux:kanban>
                </div>
            </div>
        </div>
    </div>
</div>