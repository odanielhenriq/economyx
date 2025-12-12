<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RecurringTransaction extends Model
{
    protected $fillable = [
        'description',
        'amount',
        'total_amount',
        'frequency',
        'day_of_month',
        'start_date',
        'end_date',
        'is_active',
        'category_id',
        'type_id',
        'payment_method_id',
        'credit_card_id',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recurring_transaction_user');
    }
}
