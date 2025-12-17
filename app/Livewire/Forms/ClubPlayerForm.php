<?php

namespace App\Livewire\Forms;

use Carbon\Carbon;
use Livewire\Form;
use App\Enums\Gender;
use App\Models\Member;
use App\Models\Player;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

class ClubPlayerForm extends Form
{
    public ?Player $player;

    #[Validate('required', as: 'First Name')]
    #[Validate('max:50', as: 'First Name')]
    public $first_name = '';

    #[Validate('required', as: 'Last Name')]
    #[Validate('max:50', as: 'Last Name')]
    public $last_name = '';

    #[Validate(new Enum(Gender::class))]
    public Gender $gender = Gender::Unknown;

    #[Validate('email', as: 'Email')]
    #[Validate('nullable')]
    public $email = '';

    public $tel_no = '';

    public ?Carbon $dob = null;

    public $user_id = null;

    public function setPlayer(Player $player)
    {
        $this->player = $player;
        $this->first_name = $player->first_name;
        $this->last_name = $player->last_name;
        $this->gender = $player->gender;
        $this->email = $player->email;
        $this->tel_no = $player->tel_no;
        $this->dob = $player->dob;
    }

    public function store($club)
    {
        $this->validate();

        DB::transaction(function () use ($club) {
            $maxClubPlayerId = $club->players()
                ->newPivotStatement()           // query on the pivot table
                ->where('club_id', $club->id)
                ->max('club_player_id');

            $nextClubPlayerId = ($maxClubPlayerId ?? 0) + 1;

            $player = Player::create([
                'first_name' => format_name($this->first_name),
                'last_name' => format_name($this->last_name),
                'gender' => $this->gender,
                'email' => trim($this->email) ?: null,
                'tel_no' => trim($this->tel_no) ?: null,
                'dob' => $this->dob,
            ]);

            $club->players()->attach($player->id, [
                'club_player_id' => $nextClubPlayerId,
            ]);

            return $player;
        });
    }

    public function update()
    {
        $this->validate();

        $this->player->update([
            'first_name' => format_name($this->first_name),
            'last_name' => format_name($this->last_name),
            'gender' => $this->gender,
            'email' => trim($this->email) ?: null,
            'tel_no' => trim($this->tel_no) ?: null,
            'dob' => $this->dob,
        ]);
    }
}
