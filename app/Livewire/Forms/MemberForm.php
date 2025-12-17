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

class MemberForm extends Form
{
    public ?Member $member;

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

    public function setMember(Member $member)
    {
        $this->member = $member;
        $this->first_name = $member->player->first_name;
        $this->last_name = $member->player->last_name;
        $this->gender = $member->player->gender;
        $this->email = $member->player->email;
        $this->tel_no = $member->player->tel_no;
        $this->dob = $member->player->dob;
    }

    public function store($club)
    {
        $this->validate();

        DB::transaction(function () use ($club) {
            $club_member_id = $club->members()->withTrashed()->max('club_member_id') + 1;

            $player = Player::create([
                'first_name' => trim($this->first_name),
                'last_name' => trim($this->last_name),
                'gender' => $this->gender,
                'email' => trim($this->email) ?: null,
                'tel_no' => trim($this->tel_no) ?: null,
                'dob' => $this->dob,
            ]);

            $member = $club->members()->create([
                'club_member_id' => $club_member_id,
                'player_id' => $player->id,
                'first_name' => trim($this->first_name),
                'last_name' => trim($this->last_name),
            ]);

            return $member;
        });
    }

    public function update()
    {
        $this->validate();

        DB::transaction(function () {
            $this->member->update([
                'first_name' => trim($this->first_name),
                'last_name' => trim($this->last_name),
            ]);

            $this->member->player->update([
                'gender' => $this->gender,
                'email' => trim($this->email) ?: null,
                'tel_no' => trim($this->tel_no) ?: null,
                'dob' => $this->dob,
            ]);
        });
    }
}
