<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('templates')->where(fn ($query) => $query->where('user_id', $this->user()->id))
            ],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => [
                'required', 
                'string', 
                Rule::in(array_keys(config('branding.fonts', [])))
            ],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'logo_size' => ['required', 'integer', 'min:50', 'max:300'],
            'logo_position' => ['required', 'string', Rule::in(['left', 'center', 'right'])],
            'is_default' => ['boolean'],
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
            'name.required' => 'Le nom du modèle est obligatoire.',
            'name.unique' => 'Vous avez déjà un modèle portant ce nom.',
            'primary_color.regex' => 'La couleur principale doit être au format hexadécimal (ex: #3B82F6).',
            'secondary_color.regex' => 'La couleur secondaire doit être au format hexadécimal (ex: #1E40AF).',
            'font_family.in' => 'La police sélectionnée n\'est pas valide ou n\'est pas compatible avec Factur-X.',
            'logo_size.min' => 'La taille du logo doit être d\'au moins :min px.',
            'logo_size.max' => 'La taille du logo ne peut pas dépasser :max px.',
            'logo_position.in' => 'La position du logo sélectionnée n\'est pas valide.',
        ];
    }
}