<?php

namespace App\Models;

use App\Services\GenerateInstallmentsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    // Campos permitidos em mass assignment (create/update)
    protected $fillable = [
        'description',
        'total_amount',
        'amount',
        'transaction_date',
        'due_date',
        'category_id',
        'type_id',
        'payment_method_id',
        'credit_card_id',
        'installment_number',
        'installment_total',
        'recurring_transaction_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',      // vira Carbon
        'due_date'         => 'date',
        'amount'           => 'decimal:2', // formata como decimal com 2 casas
    ];

    // Relação N:N com usuários (quem está pagando essa transação)
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function recurringTransaction()
    {
        return $this->belongsTo(RecurringTransaction::class);
    }

    /**
     * Eventos de modelo
     */
    protected static function booted()
    {
        static::created(function ($transaction) {

            // Se a transação for parcelada, gerar as parcelas
            app(GenerateInstallmentsService::class)->generate($transaction);

        });
    }
}
