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

        // Regras pra entender se é parcela (tem número E total)
        $hasNumberAndTotal = ! is_null($this->installment_number) && ! is_null($this->installment_total);
        $isInstallment = $hasNumberAndTotal;
        $installmentNumber = $this->installment_number;
        $remaining = $isInstallment ? max($this->installment_total - $installmentNumber, 0) : null;
        $installmentLabel = $isInstallment ? "{$installmentNumber}/{$this->installment_total}" : null;

        $displayAmount = (float) $this->amount;
        $baseDate = $this->due_date ?? $this->transaction_date;

        $monthFilter = $request->query('month');
        if ($monthFilter && $isInstallment && $this->relationLoaded('installments')) {
            [$filterYear, $filterMonth] = array_map('intval', explode('-', $monthFilter, 2));
            $matchingInstallment = $this->installments->first(function ($installment) use ($filterYear, $filterMonth) {
                $dueDate = $installment->due_date;

                return $dueDate
                    && (int) $dueDate->year === $filterYear
                    && (int) $dueDate->month === $filterMonth;
            });

            if ($matchingInstallment) {
                $baseDate = $matchingInstallment->due_date ?? $baseDate;
                $displayAmount = (float) $matchingInstallment->amount;
                $installmentNumber = $matchingInstallment->installment_number ?? $installmentNumber;
                $remaining = max($this->installment_total - $installmentNumber, 0);
                $installmentLabel = "{$installmentNumber}/{$this->installment_total}";
            }
        }

        // Considera despesa se o slug do type for "dc"
        $isExpense = $this->type?->slug === 'dc';

        // Quanto cada um paga (divisão igualitária)
        $share = $usersCount > 0 ? round($displayAmount / $usersCount, 2) : null;

        return [
            'id'            => $this->id,
            'description'   => $this->description,
            'total_amount'  => $this->total_amount,
            'amount'        => $displayAmount,
            'category_id'   => $this->category_id,
            'type_id'       => $this->type_id,
            'payment_method_id' => $this->payment_method_id,
            'credit_card_id' => $this->credit_card_id,
            'recurring_transaction_id' => $this->recurring_transaction_id,

            // signed_amount já traz sinal (+/-) baseado no tipo
            'signed_amount' => $isExpense ? -1 * $displayAmount : $displayAmount,

            // Data base do calendário (due_date) com fallback
            'date'          => $baseDate?->format('Y-m-d'),
            'due_date'      => $this->due_date?->format('Y-m-d'),
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),

            'installments' => [
                'number'        => $installmentNumber,
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
