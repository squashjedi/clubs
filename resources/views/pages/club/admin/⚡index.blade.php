<?php

use App\Models\Club;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.club-admin')] class extends Component
{
    public Club $club;

    public function with(): array
    {
        return [
            'hasLeagues' => $this->club->leagues()->withTrashed()->exists(),
        ];
    }
}; ?>


<div class="space-y-main">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item>Dashboard</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid grid-cols-2 gap-4">
        <x-cards.club-admin-community-total
            title="Members"
            href="{{ route('club.admin.players', [$club]) }}"
            :collection="$this->club->players()->wherePivotNull('deleted_at')"
        />
        <x-cards.club-admin-community-total
            title="Leagues"
            href="{{ route('club.admin.leagues', [$club]) }}"
            :collection="$this->club->leagues"
        />
    </div>

    @if (! $hasLeagues)
        <flux:card class="grid sm:grid-cols-2 gap-6">
            <div class="space-y-6">
                <flux:heading size="xl">Congratulations!</flux:heading>
                <div class="space-y-3">
                    <flux:text>You are now the {{ $club->name }} administrator.</flux:text>
                    <flux:text>There's no time like the present so let's get started and <span class="font-semibold">Create Your First League Session</span>.</flux:text>
                    <flux:text>If you need any help, our support team is just an email away at <flux:link href="mailto:support@reckify.com">support@reckify.com</flux:link></flux:text>
                    <flux:text class="mt-6">Best regards,</flux:text>
                    <flux:text>The Reckify Team</flux:text>
                </div>
            </div>
            <flux:card class="!bg-blue-50 !border-none space-y-4">
                <flux:heading size="lg" class="text-center">Create Your First League Session</flux:heading>
                <flux:text class="text-center">It's best to add your members first, since you'll need them when creating your league session.</flux:text>
                <div class="flex flex-col items-center">
                    <flux:button
                        href="{{ route('club.admin.members.create', [$club]) }}"
                        variant="primary"
                        icon="plus"
                    >
                        Member
                    </flux:button>
                </div>
                <flux:text class="text-center">You can still skip ahead and create a league first.</flux:text>
                <div class="flex flex-col items-center">
                    <flux:button
                        href="{{ route('club.admin.leagues.create', [$club]) }}"
                        variant="primary"
                        icon="plus"
                    >
                        League
                    </flux:button>
                </div>
            </flux:card>
        </flux:card>
    @else
        <livewire:generic.club-admin-status-leagues :lazy :$club />
    @endif
</div>
