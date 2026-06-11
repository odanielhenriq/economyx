<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class SharedExpenseService
{
    public function forMonth(
        int $year,
        int $month,
        User $user,
        ?int $partnerId = null,
        string $statusFilter = 'all'
    ): array {
        $networkUsers = $user->networkUsers();
        $networkIds = $networkUsers->pluck('id')->all();
        $hasPartners = $networkUsers->count() > 1;

        $transactions = $this->sharedTransactionsForMonth($year, $month, $user, $networkIds, $partnerId);

        $expenses = [];
        $totalShared = 0.0;
        $pendingSettlement = 0.0;
        $settledTotal = 0.0;
        $participantNames = [];

        foreach ($transactions as $transaction) {
            $expense = $this->mapExpense($transaction, $year, $month, $user);

            if ($expense === null) {
                continue;
            }

            if (! $this->matchesStatusFilter($expense, $statusFilter)) {
                continue;
            }

            $expenses[] = $expense;
            $totalShared += $expense['amount'];
            $pendingSettlement += $expense['pending_amount'];
            $settledTotal += $expense['settled_amount'];

            foreach ($expense['participants'] as $participant) {
                $participantNames[$participant['user_id']] = $participant['name'];
            }
        }

        $pendingSuggestions = $this->buildPendingSuggestions($expenses);

        $partners = $networkUsers
            ->reject(fn (User $networkUser) => $networkUser->id === $user->id)
            ->map(fn (User $networkUser) => [
                'user_id' => $networkUser->id,
                'name' => $networkUser->name,
            ])
            ->values()
            ->all();

        return [
            'month' => sprintf('%04d-%02d', $year, $month),
            'month_label' => $this->monthLabel($year, $month),
            'has_partners' => $hasPartners,
            'has_shared_expenses' => count($expenses) > 0,
            'summary' => [
                'total_shared' => round($totalShared, 2),
                'pending_settlement' => round($pendingSettlement, 2),
                'settled_total' => round($settledTotal, 2),
                'transactions_count' => count($expenses),
                'participants_count' => count($participantNames),
            ],
            'suggestions' => $pendingSuggestions,
            'expenses' => $expenses,
            'partners' => $partners,
            'filters' => [
                'partner_id' => $partnerId,
                'status' => $statusFilter,
            ],
            'payer_note' => 'Compras no cartão: quem pagou é o dono do cartão. Pagar a fatura ao banco não acerta automaticamente a parte do parceiro.',
        ];
    }

    public function splitEqually(float $amount, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        $share = round($amount / $parts, 2);
        $shares = array_fill(0, $parts, $share);
        $allocated = round($share * $parts, 2);
        $remainder = round($amount - $allocated, 2);

        if ($remainder !== 0.0) {
            $shares[0] = round($shares[0] + $remainder, 2);
        }

        return $shares;
    }

    public function resolvePayerId(Transaction $transaction): ?int
    {
        if ($transaction->paid_by_user_id) {
            return (int) $transaction->paid_by_user_id;
        }

        if ($transaction->credit_card_id && $transaction->creditCard?->owner_user_id) {
            return (int) $transaction->creditCard->owner_user_id;
        }

        $firstParticipant = $transaction->users->sortBy('id')->first();

        return $firstParticipant ? (int) $firstParticipant->id : null;
    }

    public function effectiveAmountForMonth(Transaction $transaction, int $year, int $month): ?float
    {
        $installmentsInMonth = $transaction->installments->filter(function ($installment) use ($year, $month) {
            $dueDate = $installment->due_date;

            return $dueDate
                && (int) $dueDate->year === $year
                && (int) $dueDate->month === $month;
        });

        if ($installmentsInMonth->isNotEmpty()) {
            return (float) $installmentsInMonth->sum('amount');
        }

        if ($transaction->installments->isNotEmpty()) {
            return null;
        }

        $dueDate = $transaction->due_date;

        if ($dueDate && (int) $dueDate->year === $year && (int) $dueDate->month === $month) {
            return (float) $transaction->amount;
        }

        return null;
    }

    /**
     * @param  array<int, int|string>  $networkIds
     */
    public function sharedTransactionsForMonth(
        int $year,
        int $month,
        User $user,
        array $networkIds,
        ?int $partnerId = null
    ): Collection {
        return Transaction::query()
            ->with(['users', 'type', 'creditCard.owner', 'installments', 'paidBy'])
            ->whereHas('type', function ($query) {
                $query->where('slug', 'dc');
            })
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->when($partnerId, function ($query) use ($partnerId) {
                $query->whereHas('users', function ($sub) use ($partnerId) {
                    $sub->where('users.id', $partnerId);
                });
            })
            ->where(function ($query) use ($year, $month) {
                $query->where(function ($sub) use ($year, $month) {
                    $sub->whereYear('due_date', $year)
                        ->whereMonth('due_date', $month);
                })->orWhereHas('installments', function ($sub) use ($year, $month) {
                    $sub->whereYear('due_date', $year)
                        ->whereMonth('due_date', $month);
                });
            })
            ->orderByDesc('due_date')
            ->get()
            ->filter(function (Transaction $transaction) use ($networkIds) {
                if ($transaction->users->count() < 2) {
                    return false;
                }

                return $transaction->users->every(fn (User $participant) => in_array($participant->id, $networkIds, true));
            })
            ->values();
    }

    private function mapExpense(Transaction $transaction, int $year, int $month, User $viewer): ?array
    {
        $amount = $this->effectiveAmountForMonth($transaction, $year, $month);

        if ($amount === null || $amount <= 0) {
            return null;
        }

        $participants = $transaction->users->unique('id')->values();
        $shares = $this->splitEqually($amount, $participants->count());
        $payerId = $this->resolvePayerId($transaction);
        $payer = $participants->firstWhere('id', $payerId) ?? $transaction->paidBy;

        $participantRows = [];
        $pendingAmount = 0.0;
        $settledAmount = 0.0;

        foreach ($participants as $index => $participant) {
            $share = $shares[$index];
            $isPayer = $payerId !== null && (int) $participant->id === (int) $payerId;
            $isSettled = (bool) ($participant->pivot->is_settled ?? false);
            $settlementRole = $isPayer ? 'payer' : 'debtor';

            if ($settlementRole === 'debtor') {
                if ($isSettled) {
                    $settledAmount += $share;
                } else {
                    $pendingAmount += $share;
                }
            }

            $canMarkSettled = ! $isPayer && ! $isSettled && (
                (int) $viewer->id === (int) $participant->id
                || (int) $viewer->id === (int) $payerId
            );

            $canUnsettle = ! $isPayer && $isSettled && (
                (int) $viewer->id === (int) $participant->id
                || (int) $viewer->id === (int) $payerId
            );

            $participantRows[] = [
                'user_id' => $participant->id,
                'name' => $participant->name,
                'share' => $share,
                'settlement_role' => $settlementRole,
                'is_settled' => $isSettled,
                'settlement_status' => $isPayer ? 'not_applicable' : ($isSettled ? 'settled' : 'pending'),
                'settled_at' => $participant->pivot->settled_at ?? null,
                'settled_to' => $payer && ! $isPayer ? [
                    'user_id' => $payer->id,
                    'name' => $payer->name,
                ] : null,
                'can_mark_settled' => $canMarkSettled,
                'can_unsettle' => $canUnsettle,
            ];
        }

        $expenseStatus = 'settled';
        if ($pendingAmount > 0 && $settledAmount > 0) {
            $expenseStatus = 'partial';
        } elseif ($pendingAmount > 0) {
            $expenseStatus = 'pending';
        }

        return [
            'transaction_id' => $transaction->id,
            'description' => $transaction->description,
            'due_date' => $transaction->due_date?->toDateString(),
            'amount' => round($amount, 2),
            'payer' => $payer ? [
                'user_id' => $payer->id,
                'name' => $payer->name,
            ] : null,
            'participants' => $participantRows,
            'pending_amount' => round($pendingAmount, 2),
            'settled_amount' => round($settledAmount, 2),
            'status' => $expenseStatus,
            'credit_card' => $transaction->creditCard ? [
                'id' => $transaction->creditCard->id,
                'name' => $transaction->creditCard->name,
            ] : null,
        ];
    }

    private function matchesStatusFilter(array $expense, string $statusFilter): bool
    {
        return match ($statusFilter) {
            'pending' => in_array($expense['status'], ['pending', 'partial'], true),
            'settled' => $expense['status'] === 'settled',
            default => true,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $expenses
     * @return array<int, array<string, mixed>>
     */
    private function buildPendingSuggestions(array $expenses): array
    {
        $suggestions = [];

        foreach ($expenses as $expense) {
            $payer = $expense['payer'] ?? null;

            if (! $payer) {
                continue;
            }

            foreach ($expense['participants'] as $participant) {
                if ($participant['settlement_role'] !== 'debtor' || $participant['is_settled']) {
                    continue;
                }

                $amount = $participant['share'];

                $suggestions[] = [
                    'transaction_id' => $expense['transaction_id'],
                    'from_user_id' => $participant['user_id'],
                    'from' => $participant['name'],
                    'to_user_id' => $payer['user_id'],
                    'to' => $payer['name'],
                    'amount' => $amount,
                    'description' => $expense['description'],
                    'message' => sprintf(
                        '%s deve pagar R$ %s para %s referente a %s.',
                        $participant['name'],
                        number_format($amount, 2, ',', '.'),
                        $payer['name'],
                        $expense['description']
                    ),
                ];
            }
        }

        return $suggestions;
    }

    private function monthLabel(int $year, int $month): string
    {
        $names = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return ($names[$month] ?? '') . '/' . $year;
    }
}
