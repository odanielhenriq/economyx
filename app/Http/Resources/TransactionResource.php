<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $usersCount = $this->users->count();
        $share = $usersCount > 0 ? round($this->amount / $usersCount, 2) : null;

        $hasNumberAndTotal = !is_null($this->installment_number) && !is_null($this->installment_total);
        $isInstallment  = $hasNumberAndTotal;
        $remaining      = $isInstallment ? max($this->installment_total - $this->installment_number, 0) : null;
        $installmentLabel = $isInstallment ? "{$this->installment_number}/{$this->installment_total}" : null;
        $isExpense = $this->type?->slug === 'dp';

        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount' => $this->amount,
            'signed_amount' => $isExpense ? -1 * $this->amount : $this->amount,
            'date' => $this->transaction_date->format('Y-m-d'),

            'installments' => [
                'number' => $this->installment_number,
                'total' => $this->installment_total,
                'is_installment' => $isInstallment,
                'label'          => $installmentLabel,
                'remaining'      => $remaining,
            ],
            'category' => [
                'name' => $this->category?->name,
                'color' => $this->category?->color,
            ],
            'type' => [
                'name' => $this->type?->name,
                'color' => $this->type?->color,
            ],

            'payment_method' => [
                'name' => $this->paymentMethod?->name,
            ],

            'users' => $this->users->map(fn($u) =>
            [
                'id' => $u->id,
                'name' => $u->name,
                'share_amount' => $share,
            ]),
            'totals' => [
                'total_amount' => $this->amount,
                'per_user_share' => $share,
            ]

        ];
    }
}
