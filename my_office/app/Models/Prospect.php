<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospect extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'company',
        'alias',
        'email',
        'phone',
        'address',
        'zip_code', // Added
        'city',     // Added
        'status',
        'is_favorite',
        'vat_status',
        'siret',
        'vat_number',
    ];

    protected $casts = [
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'address' => 'encrypted',
        'siret' => 'encrypted',
        'vat_status' => 'boolean',
        'is_favorite' => 'boolean',
        // 'status' => \App\Enums\ProspectStatus::class, // Assuming an enum for status
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class, 'client_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    // Added notes relationship
    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}
