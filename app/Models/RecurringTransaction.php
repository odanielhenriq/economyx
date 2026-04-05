<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model que representa um template de transação recorrente.
 * 
 * Um template define:
 * - Descrição, valor, categoria, tipo
 * - Frequência (mensal ou anual)
 * - Dia do mês de vencimento
 * - Período de validade (start_date/end_date)
 * 
 * Materialização:
 * - O comando RecurringMaterializeCommand cria transações reais
 * - Baseado neste template
 * - Aparece no dashboard como projeção antes de ser materializada
 * 
 * Relacionamentos:
 * - users: Usuários que dividem (N:N)
 * - transactions: Transações materializadas deste template
 * - category, type, paymentMethod, creditCard: Dados da transação
 */
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

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recurring_transaction_user');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
