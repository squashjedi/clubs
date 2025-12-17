<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ScoreBestOf implements ValidationRule
{
    public function __construct(public $memberScore1, public $memberScore2, public $bestOf)
    {

    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((int) $this->memberScore1 + (int) $this->memberScore2 > $this->bestOf) {
            $fail('Invalid Score');
        }
    }
}
