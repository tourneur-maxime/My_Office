<?php

namespace App\Services;

class SiretValidator
{
    /**
     * Validate a SIRET number
     *
     * Returns an array with 'valid' (boolean) and 'error' (string|null)
     *
     * @return array{valid: bool, error: string|null}
     */
    public static function validate(?string $siret): array
    {
        // Allow empty/null values (optional field)
        if (empty($siret)) {
            return ['valid' => true, 'error' => null];
        }

        // Check format: must be exactly 14 digits
        if (! preg_match('/^\d{14}$/', $siret)) {
            return [
                'valid' => false,
                'error' => 'Le numéro SIRET doit contenir 14 chiffres',
            ];
        }

        // Validate using Luhn algorithm
        if (! self::validateLuhnAlgorithm($siret)) {
            return [
                'valid' => false,
                'error' => "Le numéro SIRET n'est pas valide",
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate SIRET using Luhn algorithm (modulus 10 check)
     *
     * The Luhn algorithm for SIRET:
     * 1. Double every second digit from right to left (positions 1, 3, 5, 7, 9, 11, 13)
     * 2. If doubled value > 9, subtract 9
     * 3. Sum all digits
     * 4. If sum % 10 == 0, SIRET is valid
     */
    private static function validateLuhnAlgorithm(string $siret): bool
    {
        $sum = 0;
        $length = strlen($siret);

        // Process digits from right to left
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $siret[$length - 1 - $i];

            // Double every second digit (odd positions from right: 1, 3, 5, ...)
            if ($i % 2 === 1) {
                $digit *= 2;
                // If doubled value > 9, subtract 9
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        // Valid if sum is divisible by 10
        return ($sum % 10) === 0;
    }
}
