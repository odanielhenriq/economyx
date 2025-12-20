<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Define como uma Transaction vira JSON na API.
     */
    public function toArray(Request $request): array
    {
        // Quantas pessoas estão associadas à transação
        $usersCount = $this->users->count();

        // Quanto cada um paga (divisão igualitária)
        $share = $usersCount > 0 ? round($this->amount / $usersCount, 2) : null;

        // Regras pra entender se é parcela (tem número E total)
        $hasNumberAndTotal = !is_null($this->installment_number) && !is_null($this->installment_total);
        $isInstallment  = $hasNumberAndTotal;
        $remaining      = $isInstallment ? max($this->installment_total - $this->installment_number, 0) : null;
        $installmentLabel = $isInstallment ? "{$this->installment_number}/{$this->installment_total}" : null;

        // Considera despesa se o slug do type for "dc"
        $isExpense = $this->type?->slug === 'dc';

        $baseDate = $this->due_date ?? $this->transaction_date;

        return [
            'id'            => $this->id,
            'description'   => $this->description,
            'total_amount'  => $this->total_amount,
            'amount'        => $this->amount,

            // signed_amount já traz sinal (+/-) baseado no tipo
            'signed_amount' => $isExpense ? -1 * $this->amount : $this->amount,

            // Data base do calendário (due_date) com fallback
            'date'          => $baseDate?->format('Y-m-d'),
            'due_date'      => $this->due_date?->format('Y-m-d'),
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),

            'installments' => [
                'number'        => $this->installment_number,
                'total'         => $this->installment_total,
                'is_installment' => $isInstallment,
                'label'         => $installmentLabel,
                'remaining'     => $remaining,
            ],

            'category' => [
                'name'  => $this->category?->name,
                'color' => $this->category?->color,
            ],

            'type' => [
                'name'  => $this->type?->name,
                'color' => $this->type?->color,
            ],

            'payment_method' => [
                'name' => $this->paymentMethod?->name,
            ],

            // Informações do cartão, se houver
            'credit_card' => $this->creditCard ? [
                'id'          => $this->creditCard->id,
                'name'        => $this->creditCard->name,
                'owner_label' => $this->creditCard->owner ? $this->creditCard->owner->name : null,
            ] : null,

            // Lista de usuários com o quanto cada um paga
            'users' => $this->users->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'share_amount' => $share,
            ]),

            'totals' => [
                'total_amount'   => $this->total_amount,
                'per_user_share' => $share,
            ],
        ];
    }
}
