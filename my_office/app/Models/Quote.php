<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'template_id',
        'branding_snapshot',
        'status',
        'subtotal',
        'vat_amount',
        'total',
        'expires_at',
        'quote_number',
    ];

    protected $casts = [
        'status' => QuoteStatus::class,
        'expires_at' => 'datetime',
        'branding_snapshot' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Prospect::class, 'client_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lineItems()
    {
        return $this->hasMany(QuoteLineItem::class)->orderBy('sort_order');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
