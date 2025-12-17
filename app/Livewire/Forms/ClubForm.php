<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Club;
use App\Rules\ForbiddenSlugs;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ClubForm extends Form
{
    public ?Club $club;

    public string $name;

    public string $timezone = "Europe/London";

    protected function rules()
    {
        return [
            'name' => ['required', Rule::unique('clubs')->ignore(isset($this->club) ? $this->club->id : null), 'min:3', 'max:50', new ForbiddenSlugs],
            'timezone' => ['required'],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'name' => 'club name',
        ];
    }

    public function setClub(Club $club)
    {
        $this->club = $club;
        $this->name = $club->name;
        $this->timezone = $club->timezone;
    }

    public function store()
    {
        $this->validate();

        $club = DB::transaction(function () {
            $club = Auth::user()->clubsAdmin()->create([
                'name' => $this->name,
                'timezone' => $this->timezone,
            ]);

            return $club;
        });

        return $club;
    }

    public function update()
    {
        $this->validate();

        $this->club->update([
            'name' => $this->name,
            'timezone' => $this->timezone,
        ]);
    }
}
