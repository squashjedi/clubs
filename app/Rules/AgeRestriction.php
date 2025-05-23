<?php

namespace App\Rules;

use Closure;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;

class AgeRestriction implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value->isAfter(today()->subYears(13))) {
            $fail('Sorry you must at least 13 years old.');
        }
    }
}