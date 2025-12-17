<?php

namespace App\Livewire\Forms;

use Carbon\Carbon;
use Livewire\Form;
use App\Enums\Gender;
use App\Models\Player;
use App\Enums\PlayerRelationship;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PlayerForm extends Form
{
    public ?Player $player;

    #[Validate('required', as: 'First Name')]
    #[Validate('max:50', as: 'First Name')]
    public ?string $first_name = '';

    #[Validate('required', as: 'Last Name')]
    #[Validate('max:50', as: 'Last Name')]
    public ?string $last_name = '';

    public Gender $gender = Gender::Unknown;

    public ?Carbon $dob = null;

    public function setPlayer(Player $player)
    {
        $this->player = $player;

        $this->first_name = $this->player->first_name;
        $this->last_name = $this->player->last_name;
        $this->gender = $this->player->gender;
        $this->dob = $this->player->dob;
    }

    public function store()
    {
        $this->validate();

        DB::transaction(function () {
            $player = Player::create([
                'first_name' => format_name($this->first_name),
                'last_name' => format_name($this->last_name),
                'gender' => $this->gender,
                'dob' => $this->dob,
                'email' => Auth::user()->email,
                'tel_no' => Auth::user()->tel_no,
            ]);

            Auth::user()->players()->attach($player->id, ['relationship' => PlayerRelationship::Guardian]);
        });
    }

    public function update()
    {
        $this->validate();

        $this->player->update([
            'first_name' => format_name($this->first_name),
            'last_name' => format_name($this->last_name),
            'gender' => $this->gender,
            'dob' => $this->dob,
        ]);
    }
}
