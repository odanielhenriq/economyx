<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount' => $this->amount,
            'total_amount' => $this->total_amount,
            'frequency' => $this->frequency,
            'day_of_month' => $this->day_of_month,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_active' => (bool) $this->is_active,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'color' => $this->category?->color,
            ],
            'type' => [
                'id' => $this->type?->id,
                'name' => $this->type?->name,
                'color' => $this->type?->color,
            ],
            'payment_method' => [
                'id' => $this->paymentMethod?->id,
                'name' => $this->paymentMethod?->name,
            ],
            'credit_card' => $this->creditCard ? [
                'id' => $this->creditCard->id,
                'name' => $this->creditCard->name,
                'owner_label' => $this->creditCard->owner?->name,
            ] : null,
            'users' => $this->users?->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]),
        ];
    }
}
