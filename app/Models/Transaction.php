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
        'category_id',
        'type_id',
        'payment_method_id',
        'credit_card_id',
        'installment_number',
        'installment_total'
    ];

    protected $casts = [
        'transaction_date' => 'date',      // vira Carbon
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

    /**
     * Eventos de modelo
     */
    protected static function booted()
    {
        static::created(function ($transaction) {
            // ⚠️ Observação importante:
            // Aqui você só chama o GenerateInstallmentsService
            // se TIVER credit_card_id.
            //
            // Isso significa que empréstimos/financiamentos (sem cartão)
            // não vão gerar TransactionInstallment automaticamente.
            //
            // Se você quiser que empréstimos também gerem parcelas,
            // deveria chamar SEM o if:
            //
            // app(GenerateInstallmentsService::class)->generate($transaction);
            //
            // E deixar o service decidir se é cartão ou empréstimo.
            if ($transaction->credit_card_id) {
                app(GenerateInstallmentsService::class)->generate($transaction);
            }
        });
    }
}
