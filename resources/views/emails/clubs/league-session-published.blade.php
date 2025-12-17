@component('mail::message')
A new league session has been published at {{ $club->name }}.

@component('mail::button', ['url' => route('club.admin.leagues.sessions.show', ['club' => $club, 'league' => $league, 'session' => $session])])
{{ $league->name }}: {{ $session->active_period }}
@endcomponent

Best regards,
The Reckify Team
[www.reckify.com](www.reckify.com)
@endcomponent