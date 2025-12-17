<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Club;
use App\Models\League;

class LeagueForm extends Form
{
    public ?League $league;

    public ?string $name = null;

    public ?int $sport_id = null;

    public ?int $best_of = null;

    public ?int $tally_unit_id = null;

    public array $sports;

    protected function rules()
    {
        return [
            'name' => ['required', 'min:3', 'max:50'],
            'sport_id' => ['required'],
            'best_of' => ['required'],
            'tally_unit_id' => ['required'],
        ];
    }

    protected function messages()
    {
        return [
            'sport_id.required' => 'Please select a sport.',
            'best_of.required' => 'What is it the best of?',
            'tally_unit_id.required' => 'Please select a sport.',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'name' => 'league name',
        ];
    }

    public function setLeague(League $league)
    {
        $this->league = $league;
        $this->name = $league->name;
        $this->sport_id = $league->sport_id;
        $this->tally_unit_id = $league->tally_unit_id;
        $this->best_of = $league->best_of;
    }

    public function store(Club $club)
    {
        $this->validate();

        $club_league_id = $club->leagues()->withTrashed()->max('club_league_id') + 1;

        $league = $club->leagues()->create([
                'club_league_id' => $club_league_id,
                'name' => $this->name,
                'sport_id' =>  $this->sport_id,
                'tally_unit_id' => $this->tally_unit_id,
                'best_of' => $this->best_of,
            ]);

        return $league;
    }

    public function update()
    {
        $this->validate();

        $this->league->update([
            'name' => $this->name,
            'sport_id' => $this->sport_id,
        ]);
    }
}
