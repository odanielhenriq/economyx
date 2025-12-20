<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\Carbon;

class CashflowService
{
    public function forMonth(int $year, int $month, bool $includeProjections = true): array
    {
        $now = Carbon::now();
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();
        $schedule = new RecurringScheduleService();

        $transactions = Transaction::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('due_date')
            ->get();

        $items = $transactions->map(function (Transaction $transaction) {
            $baseDate = $transaction->due_date ?? $transaction->transaction_date;

            return [
                'id' => $transaction->id,
                'source' => 'transaction',
                'status' => 'normal',
                'due_date' => $baseDate?->toDateString(),
                'transaction_date' => $transaction->transaction_date?->toDateString(),
                'description' => $transaction->description,
                'amount' => $transaction->amount,
                'total_amount' => $transaction->total_amount,
                'category_id' => $transaction->category_id,
                'type_id' => $transaction->type_id,
                'payment_method_id' => $transaction->payment_method_id,
                'credit_card_id' => $transaction->credit_card_id,
                'recurring_transaction_id' => $transaction->recurring_transaction_id,
            ];
        })->all();

        if ($includeProjections) {
            $templates = RecurringTransaction::query()
                ->where('is_active', true)
                ->get();

            foreach ($templates as $template) {
                $dueDate = $schedule->dueDateForMonth($template, $year, $month, $now);

                if (! $dueDate) {
                    continue;
                }

                $exists = Transaction::query()
                    ->where('recurring_transaction_id', $template->id)
                    ->whereDate('due_date', $dueDate->toDateString())
                    ->exists();

                if ($exists) {
                    continue;
                }

                $items[] = [
                    'id' => null,
                    'source' => 'projection',
                    'status' => 'previsto',
                    'due_date' => $dueDate->toDateString(),
                    'transaction_date' => null,
                    'description' => $template->description,
                    'amount' => $template->amount,
                    'total_amount' => $template->total_amount ?? $template->amount,
                    'category_id' => $template->category_id,
                    'type_id' => $template->type_id,
                    'payment_method_id' => $template->payment_method_id,
                    'credit_card_id' => $template->credit_card_id,
                    'recurring_transaction_id' => $template->id,
                ];
            }
        }

        usort($items, function (array $a, array $b) {
            return strcmp($a['due_date'] ?? '', $b['due_date'] ?? '');
        });

        return $items;
    }
}
