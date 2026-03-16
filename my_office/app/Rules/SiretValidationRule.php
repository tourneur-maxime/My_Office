<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SiretValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // SIRET is nullable, so no validation if empty
        }

        // Check if SIRET is numeric and has 14 digits
        if (! preg_match('/^\d{14}$/', $value)) {
            $fail('Le numéro SIRET doit contenir 14 chiffres.');

            return;
        }

        // Luhn algorithm for SIRET validation (simplified example)
        // This is a basic Luhn check, a real SIRET validation might be more complex
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $digit = (int) $value[$i];
            if (($i % 2) === 0) { // Odd positions (1st, 3rd, etc.) from left
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        if (($sum % 10) !== 0) {
            $fail('Le numéro SIRET n\'est pas valide');
        }
    }
}
