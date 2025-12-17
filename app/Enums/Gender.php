<?php

namespace App\Enums;

enum Gender: string
{
	case Male = 'male';
	case Female = 'female';
	case Unknown = 'unknown';

	public function label()
	{
		return match($this) {
			static::Male => 'Male',
			static::Female => 'Female',
			static::Unknown => 'Unknown',
		};
	}

	public function icon()
	{
		return match($this) {
			static::Male => 'mars',
			static::Female => 'venus',
			static::Unknown => 'circle-question-mark',
		};
	}

    public function color()
    {
        return match ($this) {
            static::Male => 'text-blue-500',
            static::Female => 'text-pink-500',
            static::Unknown => 'text-zinc-500',
        };
    }

	public function pronoun()
	{
		return match($this) {
			static::Male => 'he',
			static::Female => 'she',
		};
	}
}