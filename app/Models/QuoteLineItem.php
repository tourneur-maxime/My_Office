<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteLineItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'quote_id',
        'description',
        'quantity',
        'unit_price',
        'vat_rate',
        'total',
        'sort_order',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteLineItem $item) {
            $item->total = $item->quantity * $item->unit_price * (1 + $item->vat_rate / 100);
        });

        static::updating(function (QuoteLineItem $item) {
            $item->total = $item->quantity * $item->unit_price * (1 + $item->vat_rate / 100);
        });
    }
}
