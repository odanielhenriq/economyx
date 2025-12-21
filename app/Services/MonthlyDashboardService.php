<?php

namespace App\Services;

use App\Models\CreditCardStatement;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\User;
use Carbon\Carbon;

class MonthlyDashboardService
{
    public function build(int $year, int $month, User $user): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $networkIds = $user->networkUsers()->pluck('id')->all();
        $installmentParentIds = TransactionInstallment::query()
            ->select('transaction_id')
            ->distinct()
            ->pluck('transaction_id')
            ->all();

        $transactionBase = Transaction::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            });

        if (! empty($installmentParentIds)) {
            $transactionBase->whereNotIn('id', $installmentParentIds);
        }

        $incomeTotal = (clone $transactionBase)
            ->whereHas('type', function ($query) {
                $query->where('slug', 'rc');
            })
            ->sum('amount');

        $directExpenseTotal = (clone $transactionBase)
            ->whereNull('credit_card_id')
            ->whereHas('type', function ($query) {
                $query->where('slug', 'dc');
            })
            ->where(function ($query) {
                $query->whereDoesntHave('category', function ($subQuery) {
                    $subQuery->where('slug', 'ep');
                })->whereDoesntHave('paymentMethod', function ($subQuery) {
                    $subQuery->where('slug', 'tb');
                });
            })
            ->sum('amount');

        $cards = $user->creditCards()->with('owner')->get();
        $statements = CreditCardStatement::with(['installments'])
            ->whereIn('credit_card_id', $cards->pluck('id')->all())
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('credit_card_id');

        $payablesCards = [];
        $payablesCardsTotal = 0.0;

        foreach ($cards as $card) {
            $statement = $statements->get($card->id);
            $installments = $statement?->installments ?? collect();
            $installmentsTotal = (float) $installments->sum('amount');
            $transactionIdsParceladas = $installments->pluck('transaction_id')->unique();

            if ($statement) {
                $periodStart = $statement->period_start;
                $periodEnd = $statement->period_end;
                $dueDay = $statement->due_day;
            } else {
                [$periodStart, $periodEnd] = $card->getBillingPeriodFor($year, $month);
                $dueDay = $card->due_day;
            }

            $aVistaQuery = Transaction::query()
                ->where('credit_card_id', $card->id)
                ->where(function ($query) {
                    $query->whereNull('installment_total')
                        ->orWhere('installment_total', '<=', 1);
                })
                ->whereBetween('transaction_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ]);

            if ($transactionIdsParceladas->isNotEmpty()) {
                $aVistaQuery->whereNotIn('id', $transactionIdsParceladas);
            }

            $aVistaTotal = (float) $aVistaQuery->sum('amount');
            $total = $installmentsTotal + $aVistaTotal;

            if (! $statement && $total <= 0) {
                continue;
            }

            $dueDate = $this->statementDueDate(
                $year,
                $month,
                $dueDay
            );

            $payablesCardsTotal += $total;

            $payablesCards[] = [
                'card_name' => $card->name,
                'owner_name' => $card->owner?->name,
                'due_date' => $dueDate->toDateString(),
                'total' => $total,
            ];
        }

        $loanInstallments = TransactionInstallment::with('transaction')
            ->whereNull('credit_card_statement_id')
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('transaction.users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->get();

        $payablesLoans = $loanInstallments->map(function (TransactionInstallment $installment) {
            $transaction = $installment->transaction;

            return [
                'transaction_id' => $installment->transaction_id,
                'description' => $transaction?->description,
                'due_date' => $installment->due_date?->toDateString(),
                'amount' => (float) $installment->amount,
                'installment_number' => $installment->installment_number,
                'installment_total' => $installment->installment_total,
            ];
        })->all();

        $payablesLoansTotal = (float) $loanInstallments->sum('amount');

        $loanTransactionIds = $loanInstallments->pluck('transaction_id')->unique()->all();
        $loanFallbackTransactions = Transaction::with(['category', 'paymentMethod'])
            ->whereNull('credit_card_id')
            ->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->whereNotIn('id', $loanTransactionIds)
            ->where(function ($query) {
                $query->whereHas('category', function ($subQuery) {
                    $subQuery->where('slug', 'ep');
                })->orWhereHas('paymentMethod', function ($subQuery) {
                    $subQuery->where('slug', 'tb');
                });
            })
            ->get();

        foreach ($loanFallbackTransactions as $transaction) {
            $fallback = $this->loanFallbackForMonth($transaction, $year, $month);

            if (! $fallback) {
                continue;
            }

            $payablesLoans[] = $fallback;
            $payablesLoansTotal += (float) $fallback['amount'];
        }

        usort($payablesLoans, function (array $a, array $b) {
            return strcmp($a['due_date'] ?? '', $b['due_date'] ?? '');
        });
        $expenseTotal = (float) $directExpenseTotal + $payablesCardsTotal + $payablesLoansTotal;
        $balanceTotal = (float) $incomeTotal - $expenseTotal;
        $payableTotal = $payablesCardsTotal + $payablesLoansTotal;

        $cashflowItems = app(CashflowService::class)
            ->forMonth($year, $month, true, $user);

        return [
            'cards' => [
                'income_total_month' => (float) $incomeTotal,
                'expense_total_month' => (float) $expenseTotal,
                'balance_month' => (float) $balanceTotal,
                'payable_total_month' => (float) $payableTotal,
                'breakdown' => [
                    'payable_cards_total' => (float) $payablesCardsTotal,
                    'payable_loans_total' => (float) $payablesLoansTotal,
                ],
            ],
            'lists' => [
                'payables_cards' => $payablesCards,
                'payables_loans' => $payablesLoans,
                'cashflow_items' => $cashflowItems,
            ],
        ];
    }

    private function statementDueDate(int $year, int $month, ?int $dueDay): Carbon
    {
        $day = $dueDay ?: 1;
        $base = Carbon::create($year, $month, 1)->startOfDay();
        $day = min(max($day, 1), $base->daysInMonth);

        return $base->copy()->day($day);
    }

    private function loanFallbackForMonth(Transaction $transaction, int $year, int $month): ?array
    {
        $totalInstallments = (int) ($transaction->installment_total ?: 1);

        $base = $transaction->first_due_date
            ? Carbon::parse($transaction->first_due_date)
            : Carbon::parse($transaction->transaction_date)->addMonthNoOverflow();

        $monthDiff = ($year - $base->year) * 12 + ($month - $base->month);

        if ($monthDiff < 0 || $monthDiff >= $totalInstallments) {
            return null;
        }

        $dueDate = $base->copy()->addMonthsNoOverflow($monthDiff);

        return [
            'transaction_id' => $transaction->id,
            'description' => $transaction->description,
            'due_date' => $dueDate->toDateString(),
            'amount' => (float) $transaction->amount,
            'installment_number' => $monthDiff + 1,
            'installment_total' => $totalInstallments,
        ];
    }
}
