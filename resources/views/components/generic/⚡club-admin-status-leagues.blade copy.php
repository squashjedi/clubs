<?php

use App\Models\Club;
use Livewire\Component;

new class extends Component
{
    public Club $club;

    public function with(): array
    {
        $now = now();

        return [
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
            'leaguesUnpublished' => $this->club->leagues()
                ->with(['latestSession' => function ($q) {
                    $q->unpublished()
                        ->latest('starts_at');
                }])
                ->withMax(['sessions as unpublished_starts_at' => function ($q) {
                    $q->unpublished();
                }], 'starts_at')
                ->havingNotNull('unpublished_starts_at')
                ->orderBy('unpublished_starts_at')
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
        ];
    }
};
?>


<div class="grid sm:grid-cols-2 gap-6">
    <div class="space-y-6">
        @if ($leaguesStartingSoon->count() > 0)
            <x-cards.league-status-card
                heading="Starting soon"
                :$club
                :leagues="$leaguesStartingSoon"
            />
        @endif

        @if ($leaguesInProgress->count() > 0)
            <x-cards.league-status-card
                heading="In progress"
                :$club
                :leagues="$leaguesInProgress"
            />
        @endif
    </div>

    <div class="space-y-6">
        @if ($leaguesUnpublished->count() > 0)
            <x-cards.league-status-card
                heading="Not published"
                :$club
                :leagues="$leaguesUnpublished"
            />
        @endif

        @if ($leaguesWaitingToBeProcessed->count() > 0)
            <x-cards.league-status-card
                heading="Waiting to be processed"
                :$club
                :leagues="$leaguesWaitingToBeProcessed"
            />
        @endif
    </div>
</div>
