<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Veuillez selectionner un fichier logo.',
            'logo.image' => 'Le fichier doit etre une image.',
            'logo.mimes' => 'Le logo doit etre au format PNG, JPG, JPEG, SVG ou WebP.',
            'logo.max' => 'Le logo ne doit pas depasser 2 Mo.',
        ];
    }
}
