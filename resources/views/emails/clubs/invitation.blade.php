@component('mail::message')
Hi

This invitation has been sent by the {{ $club->name }} administrator at reckify.com.

If you are {{ $player->name }} or willing to act as a guardian for {{ $player->name }} then please click 'Claim Player' to begin the process.

@component('mail::button', ['url' => route('club.players.invitations.show', [$club, $player, $invitation])])
Claim Player
@endcomponent

If you have any questions, feel free to reach out to the {{ $club->name }} administrator.

Best regards,<br>
The Reckify Team<br>
[www.reckify.com](www.reckify.com)
@endcomponent