<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model que representa uma parcela individual de uma transação parcelada.
 * 
 * Uma parcela pode ser:
 * - De cartão de crédito (tem credit_card_statement_id)
 * - De empréstimo/financiamento (credit_card_statement_id = null)
 * 
 * Campos Importantes:
 * - installment_number: Número da parcela (1, 2, 3...)
 * - installment_total: Total de parcelas
 * - amount: Valor da parcela individual
 * - year/month: Mês da fatura (para cartão) ou mês de vencimento (para empréstimo)
 * - due_date: Data de vencimento da parcela
 * 
 * Relacionamentos:
 * - transaction: Transação pai
 * - statement: Fatura do cartão (se for cartão)
 */
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
