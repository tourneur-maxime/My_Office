import { ref } from 'vue';

export function useSiretValidator() {
    const siretError = ref('');
    const siretValid = ref(false);

    const validateLuhnAlgorithm = (siret) => {
        let sum = 0;
        const length = siret.length;

        for (let i = 0; i < length; i++) {
            let digit = parseInt(siret[length - 1 - i]);

            if (i % 2 === 1) {
                // Double every second digit (odd positions from right: 1, 3, 5, ...)
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            sum += digit;
        }
        return sum % 10 === 0;
    };

    const validateSiret = (siretValue) => {
        const siret = siretValue ? String(siretValue).trim() : '';

        siretError.value = '';
        siretValid.value = false;

        if (!siret) {
            // Allow empty SIRET (optional field)
            siretValid.value = true;
            return { valid: true, error: null };
        }

        if (!/^\d{14}$/.test(siret)) {
            // Check format: must be exactly 14 digits
            siretError.value = 'Le numéro SIRET doit contenir 14 chiffres';
            return { valid: false, error: siretError.value };
        }

        if (!validateLuhnAlgorithm(siret)) {
            // Validate using Luhn algorithm
            siretError.value = "Le numéro SIRET n'est pas valide";
            return { valid: false, error: siretError.value };
        }

        siretValid.value = true;
        return { valid: true, error: null };
    };

    const clearSiretError = () => {
        siretError.value = '';
    };

    return {
        siretError,
        siretValid,
        validateSiret,
        clearSiretError,
    };
}
