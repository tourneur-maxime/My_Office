<?php

namespace App\Models;

use Guava\Sequence\Models\SequenceRule as BaseSequenceRule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SequenceRule extends BaseSequenceRule
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'pattern',
        'reset_frequency',
        'user_id', // Allow mass assignment for user_id
    ];

    /**
     * Get the user that owns the SequenceRule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
