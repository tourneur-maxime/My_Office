<?php

namespace App\Http\Requests;

use App\Rules\SiretFormatRule;
use App\Rules\UniqueEncrypted;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProspectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get the prospect from the route
        $prospectId = $this->route('prospect')->id ?? null;

        return [
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'alias' => 'nullable|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                UniqueEncrypted::forTable('prospects', 'email', $prospectId, $this->user()->id),
            ],
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'siret' => ['nullable', new SiretFormatRule],
            'vat_number' => 'nullable|string|max:20',
            'vat_status' => 'nullable|boolean',
            'status' => ['required', Rule::in(['prospect', 'client'])],
            'is_favorite' => 'nullable|boolean',
        ];
    }
}
