<?php

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use Illuminate\Foundation\Http\FormRequest; // Import the QuoteStatus Enum

class UpdateQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in(QuoteStatus::values())],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.id' => ['nullable', 'exists:quote_line_items,id'], // For existing line items
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'template_id' => ['nullable', 'exists:templates,id'],
        ];
    }
}
