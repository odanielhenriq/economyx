<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySavingsGoal extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'target_amount',
        'note',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
