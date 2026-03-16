<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->iban) {
            $this->merge([
                'iban' => str_replace(' ', '', strtoupper($this->iban)),
            ]);
        }

        if ($this->bic) {
            $this->merge([
                'bic' => str_replace(' ', '', strtoupper($this->bic)),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:255'],
            'siret' => ['required', 'string', 'size:14'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'rcs_number' => ['nullable', 'string', 'max:50'],
            'legal_form' => ['nullable', 'string', 'max:50'],
            'share_capital' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'late_payment_penalty_rate' => ['nullable', 'string', 'max:255'],
            'is_vat_exempt' => ['boolean'],
            'custom_legal_mentions' => ['nullable', 'string', 'max:2000'],
            
            // Bank Details
            'bank_name' => ['nullable', 'string', 'max:255'],
            // Robust IBAN regex: 2 letters, 2 digits, then 12 to 30 chars
            'iban' => ['nullable', 'string', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{12,30}$/'], 
            // BIC regex: 4 chars bank, 2 chars country, 2 chars location, opt 3 chars branch
            'bic' => ['nullable', 'string', 'regex:/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            
            // Payment Defaults
            'default_payment_terms' => ['nullable', 'string', 'max:1000'],
            'default_payment_delay_days' => ['nullable', 'integer', 'min:0'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'siret.size' => 'Le numéro SIRET doit contenir exactement 14 caractères.',
            'iban.regex' => 'Le format de l\'IBAN est invalide.',
            'bic.regex' => 'Le format du BIC est invalide.',
            'default_payment_delay_days.integer' => 'Le délai de paiement doit être un nombre entier.',
            'default_payment_delay_days.min' => 'Le délai de paiement ne peut pas être négatif.',
        ];
    }
}