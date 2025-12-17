<?php

use App\Models\Club;
use App\Models\Player;
use App\Models\Result;
use App\Models\Entrant;
use Livewire\Component;
use App\Models\Contestant;
use App\Models\Invitation;
use Livewire\Attributes\Layout;
use App\Enums\PlayerRelationship;
use Illuminate\Support\Facades\Auth;
use App\Actions\Players\ClaimPlayerInClubAction;

new #[Layout('layouts.club-front')] class extends Component
{
    public Club $club;

    public Player $player;

    public Invitation $invitation;

    public ?int $selectedUserPlayerId = null;

    public function claim()
    {
        $authUserPlayer = Auth::user()->players()->find($this->selectedUserPlayerId);

        $newPlayer = app(ClaimPlayerInClubAction::class)->execute($this->club, $authUserPlayer, $this->player, $this->invitation);

        $this->redirectRoute('settings.players.edit', ['player' => $newPlayer]);
    }

    public function with(): array
    {
        return [
            'players' => Auth::user()->players,
        ];
    }
};
?>

<div class="space-y-main">
    <flux:card class="space-y-3">
        <flux:heading size="xl">{{ $player->name }}</flux:heading>
        <flux:text>This player profile was created by {{ $this->club->name }}. Claiming it links this profile to your account so that you can submit results for this player.</flux:text>
    </flux:card>

    <form
        x-data="{
            selected: false
        }"
        wire:submit="claim"
    >
        <flux:card class="space-y-3">
            <flux:heading size="lg">How do you want to connect this player to your account?</flux:heading>
            <flux:text>Choose one of your existing player profiles, or create a new one based on this player.</flux:text>

            <flux:radio.group
                variant="cards"
                wire:model="selectedUserPlayerId"
                x-model="selected"
                class="flex-col"
            >
                <flux:label>Use an existing player profile</flux:label>
                <flux:description class="text-xs">We’ll merge the club player ({{ $player->name }}) into the profile you select. </flux:description>

                    @foreach ($players as $userPlayer)
                        @php
                            $alreadyInClub = $userPlayer->clubs()->whereKey($club->id)->exists();
                        @endphp
                        <flux:radio
                            :value="$userPlayer->id"
                            :disabled="$alreadyInClub"
                        >
                            <flux:radio.indicator />

                            <div class="flex-1">
                                <flux:heading>{{ $userPlayer->name }} {{ $userPlayer->pivot->relationship === PlayerRelationship::Self ? '(You)' : '' }}</flux:heading>
                            </div>
                        </flux:radio>
                    @endforeach
                <flux:separator text="or" />
                <flux:label>Create a new player profile</flux:label>
                <flux:description class="text-xs">We'll create a new player profile for you using this club player profile.</flux:description>
                <flux:radio value="0">
                    <flux:radio.indicator />

                    <div class="flex-1">
                        <flux:heading>Create a new player from "{{ $player->name }}"</flux:heading>
                        <flux:description class="text-xs">Recommended if none of your existing profiles match.</flux:description>
                    </div>
                </flux:radio>

            </flux:radio.group>

            <div class="flex items-center justify-between gap-6">
                <flux:description class="text-xs">By claiming this player, you confirm that you are "{{ $player->name }}" or manage their account (e.g. a junior or family member).</flux:description>
                <flux:button
                    icon:trailing="arrow-right"
                    type="submit"
                    variant="primary"
                    x-bind:disabled="!selected"
                    :loading="false"
                >
                    Claim this player
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>