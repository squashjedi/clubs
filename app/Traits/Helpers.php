<?php
namespace App\Traits;

use Illuminate\Support\Str;

trait Helpers {

	public function formatName($name) {
		$separator = (Str::of($name)->contains(' ')
			? ' '
			: (Str::of($name)->contains('-')
				? '-'
				: ' ')
			);

		$name = Str::of(strtolower($name))
			->explode($separator)
			->map(function (string $name) {
				if (Str::startsWith($name, 'mc')) {
					$pos = stripos($name, "Mc");
					$name[$pos + 2] = strtoupper($name[$pos + 2]);
				} else if (Str::startsWith($name, "o'")) {
					$name[2] = strtoupper($name[2]);
				}

				return ucfirst($name);
			})
			->implode($separator);

		return $name;
	}

	public function dateForHumans($date)
	{
		return $date->format(
			$date->year === now()->year
				? 'M d, g:i A'
				: 'M d, Y, g:i A'
		);
	}

}