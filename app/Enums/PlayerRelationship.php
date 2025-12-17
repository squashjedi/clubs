<?php

namespace App\Enums;

enum PlayerRelationship: string
{
	case Self = 'self';
	case Guardian = 'guardian';

	public function label()
	{
		return match($this) {
			static::Self => 'Self',
			static::Guardian => 'Guardian',
		};
	}
}