<?php

namespace App\Http\Requests;

use App\Rules\SiretFormatRule;
use App\Rules\UniqueEncrypted;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProspectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Handled by controller policy checks
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $prospect = $this->route('prospect');
        $prospectId = $prospect ? $prospect->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                UniqueEncrypted::forTable('prospects', 'email', $prospectId, $this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:255'],
            'siret' => ['nullable', new SiretFormatRule],
            'vat_status' => ['nullable', 'boolean'],
            'status' => ['required', 'string', Rule::in(['prospect', 'client'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'company.required' => 'Le nom de l\'entreprise est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'Le format de l\'adresse email est invalide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'siret.max' => 'Le numéro SIRET doit contenir au maximum 14 chiffres.',
            'vat_status.in' => 'Le statut TVA sélectionné est invalide.',
        ];
    }
}
