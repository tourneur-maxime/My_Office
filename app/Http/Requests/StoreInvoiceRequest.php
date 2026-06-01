<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in the controller
    }

    public function rules(): array
    {
        $rules = [
            'client_id' => [
                $this->route('client') ? 'nullable' : 'required',
                'exists:prospects,id',
            ],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'layout_configuration' => ['nullable', 'array'],
            'service_date' => ['nullable', 'date'],
        ];

        return $rules;
    }
}
