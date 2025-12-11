<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCardStatement extends Model
{
    protected $fillable = [
        'credit_card_id',
        'year',
        'month',
        'period_start',
        'period_end',
        'closing_day',
        'due_day',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function card()
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }

    public function installments()
    {
        return $this->hasMany(TransactionInstallment::class);
    }

    public function scopeForMonth($query, $cardId, $year, $month)
    {
        return $query->where('credit_card_id', $cardId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}
