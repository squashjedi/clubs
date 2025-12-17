<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateScore implements ValidationRule
{
    public function __construct(public $scoreA, public $scoreB, public $bestOf) { }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((int) $this->scoreA + (int) $this->scoreB > $this->bestOf) {
            $fail('Invalid Score');
        }
    }
}
