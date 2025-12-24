<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class CashflowService
{
    public function forMonth(int $year, int $month, bool $includeProjections = true, ?User $user = null): array
    {
        $now = Carbon::now();
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();
        $schedule = new RecurringScheduleService();

        $networkIds = $user ? $user->networkUsers()->pluck('id')->all() : [];

        // Transações à vista (excluindo parceladas)
        $installmentParentIds = \App\Models\TransactionInstallment::query()
            ->select('transaction_id')
            ->distinct()
            ->pluck('transaction_id')
            ->all();

        $transactionsQuery = Transaction::query()
            ->with('creditCard')
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('due_date');

        if ($user) {
            $transactionsQuery->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            });
        }

        if (!empty($installmentParentIds)) {
            $transactionsQuery->whereNotIn('id', $installmentParentIds);
        }

        $transactions = $transactionsQuery->get();

        $items = $transactions->map(function (Transaction $transaction) {
            $baseDate = $transaction->due_date ?? $transaction->transaction_date;
            $card = $transaction->creditCard;

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
                'credit_card_name' => $card?->name,
                'recurring_transaction_id' => $transaction->recurring_transaction_id,
            ];
        })->all();

        // Adiciona parcelas de cartão com due_date no mês
        $cardInstallments = \App\Models\TransactionInstallment::query()
            ->whereNotNull('credit_card_statement_id')
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->with(['transaction.category', 'transaction.type', 'transaction.paymentMethod', 'transaction.creditCard'])
            ->orderBy('due_date')
            ->get();

        if ($user) {
            $cardInstallments = $cardInstallments->filter(function ($inst) use ($networkIds) {
                return $inst->transaction && $inst->transaction->users->pluck('id')->intersect($networkIds)->isNotEmpty();
            });
        }

        $installmentItems = $cardInstallments->map(function (\App\Models\TransactionInstallment $installment) {
            $transaction = $installment->transaction;
            $card = $transaction?->creditCard;

            return [
                'id' => $transaction?->id,
                'source' => 'installment',
                'status' => 'normal',
                'due_date' => $installment->due_date?->toDateString(),
                'transaction_date' => $transaction?->transaction_date?->toDateString(),
                'description' => ($transaction?->description ?? '') . " (Parcela {$installment->installment_number}/{$installment->installment_total})",
                'amount' => (float) $installment->amount,
                'total_amount' => (float) $transaction?->total_amount,
                'category_id' => $transaction?->category_id,
                'type_id' => $transaction?->type_id,
                'payment_method_id' => $transaction?->payment_method_id,
                'credit_card_id' => $transaction?->credit_card_id,
                'credit_card_name' => $card?->name,
                'recurring_transaction_id' => $transaction?->recurring_transaction_id,
                'installment_number' => $installment->installment_number,
                'installment_total' => $installment->installment_total,
            ];
        })->all();

        $items = array_merge($items, $installmentItems);

        if ($includeProjections) {
            $templatesQuery = RecurringTransaction::query()
                ->where('is_active', true);

            if ($user) {
                $networkIds = $user->networkUsers()->pluck('id')->all();
                $templatesQuery->whereHas('users', function ($query) use ($networkIds) {
                    $query->whereIn('users.id', $networkIds);
                });
            }

            $templates = $templatesQuery->get();

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
