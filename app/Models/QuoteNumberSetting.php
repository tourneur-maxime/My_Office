<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteNumberSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prefix',
        'suffix',
        'last_number',
        'digit_count',
        'include_year',
        'counter_year',
    ];

    protected $casts = [
        'include_year' => 'boolean',
        'last_number' => 'integer',
        'digit_count' => 'integer',
        'counter_year' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}