<?php

use App\Models\Club;
use App\Models\League;
use App\Models\Session;
use Livewire\Component;

new class extends Component
{
    public Club $club;

    public League $league;

    public Session $session;

    public function mount()
    {
        $this->redirectRoute('club.admin.leagues.sessions.entrants', ['club' => $this->club, 'league' => $this->league, 'session' => $this->session]);
    }
}; ?>

<div>
    //
</div>
