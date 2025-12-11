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

    // Converte period_start/period_end pra objetos Carbon automaticamente
    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    // Relaciona com o cartão
    public function card()
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }

    // Uma fatura tem muitas parcelas (TransactionInstallment)
    public function installments()
    {
        return $this->hasMany(TransactionInstallment::class);
    }

    // Atalho pra buscar a fatura de um cartão em um ano/mês
    public function scopeForMonth($query, $cardId, $year, $month)
    {
        return $query->where('credit_card_id', $cardId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}
