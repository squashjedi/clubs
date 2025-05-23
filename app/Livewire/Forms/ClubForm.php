<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use App\Models\Club;
use App\Models\Sport;
use App\Rules\ForbiddenSlugs;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;

class ClubForm extends Form
{
    public ?Club $club;

    public string $name;

    public array $sports;

    public string $timezone = "Europe/London";

    protected function rules()
    {
        return [
            'name' => ['required', Rule::unique('clubs')->ignore(isset($this->club) ? $this->club->id : null), 'min:3', 'max:50', new ForbiddenSlugs],
            'sports' => ['array', 'required'],
            'timezone' => ['required'],
        ];
    }

    protected function messages()
    {
        return [
            'sports.required' => 'You must select at least one sport.'
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
        $this->sports = collect($club->sports->pluck('id'))->toArray();
        $this->timezone = $club->timezone;
    }

    public function store()
    {
        $this->validate();

        $club = DB::transaction(function () {
            $club = auth()->user()->clubsAdmin()->create([
                'name' => $this->name,
                'timezone' => $this->timezone,
            ]);

            $club->sports()->attach($this->sports);

            return $club;
        });

        return $club;
    }

    public function update()
    {
        $this->validate();

        DB::transaction(function () {
            $this->club->update([
                'name' => $this->name,
                'timezone' => $this->timezone,
            ]);

            $this->club->sports()->sync($this->sports);
        });
    }
}
