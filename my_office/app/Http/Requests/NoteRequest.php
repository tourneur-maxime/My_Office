<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization logic for notes can be more complex,
        // but for now, we'll assume it's handled by policy or controller.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'content' => ['required', 'string', 'max:1000'],
            // prospect_id will be implicit from the route
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
            'content.required' => 'Le contenu de la note est obligatoire.',
            'content.max' => 'Le contenu de la note ne doit pas dépasser 1000 caractères.',
        ];
    }
}
