<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use App\Models\Member;
use Livewire\Attributes\Validate;

class MemberForm extends Form
{
    public ?Member $member;

    #[Validate('required', as: 'First Name')]
    #[Validate('max:50', as: 'First Name')]
    public $first_name = '';

    #[Validate('required', as: 'Last Name')]
    #[Validate('max:50', as: 'Last Name')]
    public $last_name = '';

    public $user_id = null;

    public function setMember(Member $member)
    {
        $this->member = $member;
        $this->first_name = $member->first_name;
        $this->last_name = $member->last_name;
        $this->user_id = $member->user_id;
    }

    public function store($club)
    {
        $this->validate();

       $club_member_id = $club->members()->max('club_member_id') + 1;

        $member = $club->members()->create([
            'club_member_id' => $club_member_id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ]);

        return $member;
    }

    public function update()
    {
        $this->validate();

        $this->member->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'user_id' => $this->user_id,
        ]);
    }
}
