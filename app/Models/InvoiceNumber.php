<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prefix',
        'suffix',
        'digit_count',
        'counter_year',
        'reset_frequency',
        'current_number',
        'current_credit_note_number',
        'last_generated_at',
        'separator',
        'include_year',
    ];

    protected $casts = [
        'last_generated_at' => 'datetime',
        'include_year' => 'boolean',
    ];

    /**
     * Get the user that owns the InvoiceNumber.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
