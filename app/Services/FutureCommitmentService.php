<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\User;
use Carbon\Carbon;

class FutureCommitmentService
{
    public function forDashboard(int $year, int $month, User $user, int $range = 3): array
    {
        $months = [];
        $cursor = Carbon::create($year, $month, 1)->startOfMonth();

        for ($i = 0; $i < $range; $i++) {
            $cursor->addMonthNoOverflow();
            $months[] = $this->commitmentsForMonth($cursor->year, $cursor->month, $user);
        }

        $hasCommitments = collect($months)->contains(fn (array $item) => ($item['total'] ?? 0) > 0);

        return [
            'months' => $months,
            'range' => $range,
            'has_commitments' => $hasCommitments,
            'includes_card_statements' => false,
            'note' => 'Este resumo considera parcelas e contas fixas previstas. Faturas futuras entram conforme suas compras forem lançadas.',
        ];
    }

    public function commitmentsForMonth(int $year, int $month, User $user): array
    {
        $networkIds = $user->networkUsers()->pluck('id')->all();
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $installments = TransactionInstallment::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('transaction.users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->get();

        $installmentsTotal = (float) $installments->sum('amount');

        $loanTransactionIds = $installments
            ->whereNull('credit_card_statement_id')
            ->pluck('transaction_id')
            ->unique()
            ->all();

        $loanFallbackItems = $this->loanFallbackItemsForMonth($year, $month, $networkIds, $loanTransactionIds);
        $loanFallbackTotal = (float) collect($loanFallbackItems)->sum('amount');

        $installmentsTotal += $loanFallbackTotal;

        $recurring = $this->recurringExpensesForMonth($year, $month, $networkIds);
        $recurringTotal = (float) collect($recurring)->sum('amount');

        $itemsCount = $installments->count() + count($loanFallbackItems) + count($recurring);
        $total = $installmentsTotal + $recurringTotal;

        return [
            'month' => sprintf('%04d-%02d', $year, $month),
            'label' => $this->monthLabel($year, $month),
            'total' => round($total, 2),
            'installments_total' => round($installmentsTotal, 2),
            'recurring_total' => round($recurringTotal, 2),
            'card_statements_total' => 0.0,
            'items_count' => $itemsCount,
            'is_estimated' => $recurringTotal > 0 || $loanFallbackTotal > 0,
        ];
    }

    /**
     * @param  array<int, int|string>  $networkIds
     * @param  array<int, int|string>  $excludeTransactionIds
     * @return array<int, array<string, mixed>>
     */
    private function loanFallbackItemsForMonth(
        int $year,
        int $month,
        array $networkIds,
        array $excludeTransactionIds
    ): array {
        $loanTransactions = Transaction::query()
            ->whereNull('credit_card_id')
            ->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->when(! empty($excludeTransactionIds), function ($query) use ($excludeTransactionIds) {
                $query->whereNotIn('id', $excludeTransactionIds);
            })
            ->where(function ($query) {
                $query->whereHas('category', function ($subQuery) {
                    $subQuery->where('slug', 'ep');
                })->orWhereHas('paymentMethod', function ($subQuery) {
                    $subQuery->where('slug', 'tb');
                });
            })
            ->get();

        $items = [];

        foreach ($loanTransactions as $transaction) {
            $fallback = $this->loanFallbackForMonth($transaction, $year, $month);

            if ($fallback) {
                $items[] = $fallback;
            }
        }

        return $items;
    }

    /**
     * @param  array<int, int|string>  $networkIds
     * @return array<int, array<string, mixed>>
     */
    private function recurringExpensesForMonth(int $year, int $month, array $networkIds): array
    {
        $schedule = new RecurringScheduleService();
        $now = Carbon::now();

        $templates = RecurringTransaction::query()
            ->with('type')
            ->where('is_active', true)
            ->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->get();

        $items = [];

        foreach ($templates as $template) {
            if ($template->type?->slug !== 'dc') {
                continue;
            }

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
                'recurring_transaction_id' => $template->id,
                'description' => $template->description,
                'due_date' => $dueDate->toDateString(),
                'amount' => (float) $template->amount,
            ];
        }

        return $items;
    }

    private function monthLabel(int $year, int $month): string
    {
        $names = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return ($names[$month] ?? '') . '/' . $year;
    }

    private function loanFallbackForMonth(Transaction $transaction, int $year, int $month): ?array
    {
        $totalInstallments = (int) ($transaction->installment_total ?: 1);
        $startInstallmentNumber = (int) ($transaction->installment_number ?: 1);

        $base = $transaction->first_due_date
            ? Carbon::parse($transaction->first_due_date)
            : Carbon::parse($transaction->transaction_date)->addMonthNoOverflow();

        $targetDate = Carbon::create($year, $month, 1);

        if ($startInstallmentNumber > 1) {
            $parcela1Date = $targetDate->copy()->subMonthsNoOverflow($startInstallmentNumber - 1);
            $parcela1Date->day(min($base->day, $parcela1Date->daysInMonth));
            $base = $parcela1Date;
        }

        $monthDiff = ($targetDate->year - $base->year) * 12 + ($targetDate->month - $base->month);
        $installmentNumber = $monthDiff + 1;

        if ($installmentNumber < $startInstallmentNumber || $installmentNumber > $totalInstallments) {
            return null;
        }

        $dueDate = $targetDate->copy()->day(min($base->day, $targetDate->daysInMonth));

        return [
            'transaction_id' => $transaction->id,
            'description' => $transaction->description,
            'due_date' => $dueDate->toDateString(),
            'amount' => (float) $transaction->amount,
            'installment_number' => $installmentNumber,
            'installment_total' => $totalInstallments,
        ];
    }
}
