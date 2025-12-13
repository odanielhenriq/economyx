<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionInstallment extends Model
{
    protected $fillable = [
        'transaction_id',
        'credit_card_statement_id',
        'installment_number',
        'installment_total',
        'amount',
        'year',
        'month',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function statement()
    {
        return $this->belongsTo(CreditCardStatement::class, 'credit_card_statement_id');
    }
}
