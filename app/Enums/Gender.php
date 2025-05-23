<?php

namespace App\Enums;

enum Gender: string
{
	case Male = 'm';
	case Female = 'f';

	public function label()
	{
		return match($this) {
			static::Male => 'Male',
			static::Female => 'Female',
		};
	}
}