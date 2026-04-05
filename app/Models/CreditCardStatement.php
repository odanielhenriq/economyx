<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model que representa uma fatura de cartão de crédito.
 * 
 * Uma fatura agrupa:
 * - Parcelas de compras parceladas
 * - Compras à vista do período
 * 
 * Campos Importantes:
 * - year/month: Mês de vencimento da fatura
 * - period_start/period_end: Período de fechamento (quais compras entram)
 * - closing_day: Dia de fechamento
 * - due_day: Dia de vencimento
 * 
 * Relacionamentos:
 * - creditCard (ou card): Cartão dono da fatura
 * - installments: Parcelas que compõem a fatura
 */
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

    // Alias para compatibilidade
    public function creditCard()
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
