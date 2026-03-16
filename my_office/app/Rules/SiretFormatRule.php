<?php

namespace App\Rules;

use App\Services\SiretValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SiretFormatRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow empty/null values (handled by nullable rule in form request)
        if (empty($value)) {
            return;
        }

        // Validate SIRET using the SiretValidator service
        $result = SiretValidator::validate($value);

        if (! $result['valid']) {
            $fail($result['error']);
        }
    }
}
