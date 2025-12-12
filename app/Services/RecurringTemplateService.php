<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use Carbon\Carbon;

class RecurringTemplateService
{
    public function createFromTransactionData(array $data, array $userIds, ?int $dayOfMonth, ?string $frequency): RecurringTransaction
    {
        $day = $dayOfMonth ?: Carbon::parse($data['transaction_date'])->day;

        $recurring = RecurringTransaction::create([
            'description'        => $data['description'],
            'amount'             => $data['amount'],
            'total_amount'       => $data['total_amount'] ?? $data['amount'],
            'frequency'          => $frequency ?? 'monthly',
            'day_of_month'       => $day,
            'start_date'         => $data['transaction_date'],
            'category_id'        => $data['category_id'],
            'type_id'            => $data['type_id'],
            'payment_method_id'  => $data['payment_method_id'],
            'credit_card_id'     => $data['credit_card_id'] ?? null,
            'is_active'          => true,
        ]);

        $recurring->users()->sync($userIds);

        return $recurring;
    }
}
