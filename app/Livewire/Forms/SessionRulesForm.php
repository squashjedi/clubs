<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\League;
use App\Models\Session;
use Livewire\Attributes\Validate;

class SessionRulesForm extends Form
{
    public ?Session $session = null;

    #[Validate('required')]
    public int $pts_win = 0;

    #[Validate('required')]
    public int $pts_draw = 0;

    #[Validate('required')]
    public int $pts_tally = 0;

    #[Validate('required')]
    public int $pts_per_set = 0;

    #[Validate('required')]
    public int $pts_play = 0;

    public function setSession(Session $session)
    {
        $this->session = $session;

        $this->fill([
            'pts_win'     => (int) $session->pts_win,
            'pts_draw'    => (int) $session->pts_draw,
            'pts_per_set' => (int) $session->pts_per_set,
            'pts_play'    => (int) $session->pts_play,
        ]);
    }

    public function rules(): array
    {
        return [
            'pts_win'     => ['required','integer','between:0,3'],
            'pts_draw'    => ['required','integer','between:0,1'],
            'pts_per_set' => ['required','integer','between:0,1'],
            'pts_play'    => ['required','integer','between:0,1'],
        ];
    }

    public function update()
    {
        $this->validate();

        if (! $this->session) return;

        $this->session->update([
            'pts_win' => $this->pts_win,
            'pts_draw' => $this->pts_draw,
            'pts_per_set' => $this->pts_per_set,
            'pts_play' => $this->pts_play,
        ]);
    }
}
