<?php

namespace App\Rules;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;

class ForbiddenSlugs implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (collect(config('app.forbidden_slugs'))->contains(Str::lower($value))) {
            $fail('The :attribute field is forbidden.');
        }
    }
}
