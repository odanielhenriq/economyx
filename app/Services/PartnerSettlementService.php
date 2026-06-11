<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class PartnerSettlementService
{
    public function forMonth(int $year, int $month, User $user): array
    {
        $networkUsers = $user->networkUsers();
        $networkIds = $networkUsers->pluck('id')->all();
        $hasPartners = $networkUsers->count() > 1;

        $transactions = $this->sharedTransactionsForMonth($year, $month, $user, $networkIds);

        $participantStats = [];
        $totalShared = 0.0;
        $processedCount = 0;

        foreach ($transactions as $transaction) {
            $amount = $this->effectiveAmountForMonth($transaction, $year, $month);

            if ($amount === null || $amount <= 0) {
                continue;
            }

            $participants = $transaction->users->unique('id')->values();
            $participantCount = $participants->count();

            if ($participantCount < 2) {
                continue;
            }

            $shares = $this->splitEqually($amount, $participantCount);
            $payerId = $this->resolvePayerId($transaction);

            $totalShared += $amount;
            $processedCount++;

            foreach ($participants as $index => $participant) {
                $this->initParticipant($participantStats, $participant);
                $participantStats[$participant->id]['share'] += $shares[$index];
            }

            if ($payerId && isset($participantStats[$payerId])) {
                $participantStats[$payerId]['paid'] += $amount;
            } elseif ($payerId) {
                $payer = User::find($payerId);
                if ($payer) {
                    $this->initParticipant($participantStats, $payer);
                    $participantStats[$payerId]['paid'] += $amount;
                }
            }
        }

        $participants = collect($participantStats)
            ->map(function (array $stats) {
                $balance = round($stats['paid'] - $stats['share'], 2);

                return [
                    'user_id' => $stats['user_id'],
                    'name' => $stats['name'],
                    'paid' => round($stats['paid'], 2),
                    'share' => round($stats['share'], 2),
                    'balance' => $balance,
                    'status' => $this->balanceStatus($balance),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();

        $suggestions = $this->buildSuggestions($participants);

        return [
            'month' => sprintf('%04d-%02d', $year, $month),
            'month_label' => $this->monthLabel($year, $month),
            'total_shared' => round($totalShared, 2),
            'transactions_count' => $processedCount,
            'participants_count' => count($participants),
            'has_partners' => $hasPartners,
            'has_shared_expenses' => $totalShared > 0,
            'participants' => $participants,
            'suggestions' => $suggestions,
            'payer_note' => 'Compras no cartão: quem paga é o dono do cartão. Demais lançamentos: quem registrou a transação.',
        ];
    }

    /**
     * @param  array<int, int|string>  $networkIds
     */
    private function sharedTransactionsForMonth(int $year, int $month, User $user, array $networkIds): Collection
    {
        return Transaction::query()
            ->with(['users', 'type', 'creditCard.owner', 'installments'])
            ->whereHas('type', function ($query) {
                $query->where('slug', 'dc');
            })
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
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
            ->get()
            ->filter(function (Transaction $transaction) use ($networkIds) {
                if ($transaction->users->count() < 2) {
                    return false;
                }

                return $transaction->users->every(fn (User $participant) => in_array($participant->id, $networkIds, true));
            })
            ->values();
    }

    private function effectiveAmountForMonth(Transaction $transaction, int $year, int $month): ?float
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
     * @return array<int, float>
     */
    private function splitEqually(float $amount, int $parts): array
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

    private function resolvePayerId(Transaction $transaction): ?int
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

    /**
     * @param  array<int, array<string, mixed>>  $participantStats
     */
    private function initParticipant(array &$participantStats, User $user): void
    {
        if (isset($participantStats[$user->id])) {
            return;
        }

        $participantStats[$user->id] = [
            'user_id' => $user->id,
            'name' => $user->name,
            'paid' => 0.0,
            'share' => 0.0,
        ];
    }

    private function balanceStatus(float $balance): string
    {
        if ($balance > 0.005) {
            return 'receives';
        }

        if ($balance < -0.005) {
            return 'owes';
        }

        return 'settled';
    }

    /**
     * @param  array<int, array<string, mixed>>  $participants
     * @return array<int, array<string, mixed>>
     */
    private function buildSuggestions(array $participants): array
    {
        $debtors = collect($participants)
            ->filter(fn (array $p) => $p['balance'] < -0.005)
            ->sortBy('balance')
            ->values();

        $creditors = collect($participants)
            ->filter(fn (array $p) => $p['balance'] > 0.005)
            ->sortByDesc('balance')
            ->values();

        $suggestions = [];

        $debtorBalances = $debtors->mapWithKeys(fn ($p) => [$p['user_id'] => abs($p['balance'])])->all();
        $creditorBalances = $creditors->mapWithKeys(fn ($p) => [$p['user_id'] => $p['balance']])->all();

        $debtorIndex = 0;
        $creditorIndex = 0;

        while ($debtorIndex < count($debtors) && $creditorIndex < count($creditors)) {
            $debtor = $debtors[$debtorIndex];
            $creditor = $creditors[$creditorIndex];

            $debtorId = $debtor['user_id'];
            $creditorId = $creditor['user_id'];

            $amount = round(min($debtorBalances[$debtorId], $creditorBalances[$creditorId]), 2);

            if ($amount <= 0) {
                break;
            }

            $suggestions[] = [
                'from_user_id' => $debtorId,
                'from' => $debtor['name'],
                'to_user_id' => $creditorId,
                'to' => $creditor['name'],
                'amount' => $amount,
                'message' => sprintf(
                    '%s deve pagar R$ %s para %s.',
                    $debtor['name'],
                    number_format($amount, 2, ',', '.'),
                    $creditor['name']
                ),
            ];

            $debtorBalances[$debtorId] = round($debtorBalances[$debtorId] - $amount, 2);
            $creditorBalances[$creditorId] = round($creditorBalances[$creditorId] - $amount, 2);

            if ($debtorBalances[$debtorId] <= 0.005) {
                $debtorIndex++;
            }

            if ($creditorBalances[$creditorId] <= 0.005) {
                $creditorIndex++;
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
