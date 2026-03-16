<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'zip_code',
        'city',
        'siret',
        'siren',
        'vat_number',
        'rcs_number',
        'legal_form',
        'share_capital',
        'payment_terms',
        'late_payment_penalty_rate',
        'is_vat_exempt',
        'custom_legal_mentions',
        'iban',
        'vat_status',
        'invoice_numbering_format',
        'quote_numbering_format',
        'logo_path',
        'logo_size',
        'logo_position',
        'primary_color',
        'secondary_color',
        'font_family',
        'bank_name',
        'bic',
        'bank_account_holder',
        'default_payment_terms',
        'default_payment_delay_days',
        'invoice_number_reset_frequency',
        'email',
        'phone',
    ];

    protected $casts = [
        'address' => 'encrypted',
        'siret' => 'encrypted',
        'iban' => 'encrypted',
        'bic' => 'encrypted',
        'bank_name' => 'encrypted',
        'bank_account_holder' => 'encrypted',
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'is_vat_exempt' => 'boolean',
        'share_capital' => 'decimal:2',
        'default_payment_delay_days' => 'integer',
        'logo_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}