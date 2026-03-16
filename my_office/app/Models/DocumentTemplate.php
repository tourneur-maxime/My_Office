<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'blocks',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
