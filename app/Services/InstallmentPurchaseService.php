<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InstallmentPurchaseService
{
    public function forUser(User $user, array $filters = []): array
    {
        $today = Carbon::today();
        $statusFilter = $filters['status'] ?? 'active';
        $networkIds = $user->networkUsers()->pluck('id')->all();

        $transactions = $this->installmentPurchasesQuery($networkIds, $filters)->get();

        $items = $transactions
            ->filter(fn (Transaction $transaction) => $this->isWithinNetwork($transaction, $networkIds))
            ->filter(fn (Transaction $transaction) => $transaction->installments->isNotEmpty())
            ->map(fn (Transaction $transaction) => $this->mapPurchase($transaction, $today))
            ->filter(fn (array $item) => $this->matchesStatusFilter($item, $statusFilter))
            ->sortBy([
                ['status', 'asc'],
                ['next_due_date', 'asc'],
                ['description', 'asc'],
            ])
            ->values()
            ->all();

        $activeItems = collect($items)->filter(fn (array $item) => $item['status'] !== 'completed')->values();

        return [
            'summary' => $this->buildSummary($activeItems, $items, $today),
            'items' => $items,
            'filters' => [
                'status' => $statusFilter,
                'credit_card_id' => $filters['credit_card_id'] ?? null,
                'category_id' => $filters['category_id'] ?? null,
                'purchase_from' => $filters['purchase_from'] ?? null,
                'purchase_to' => $filters['purchase_to'] ?? null,
            ],
            'options' => $this->buildFilterOptions($networkIds),
            'has_items' => count($items) > 0,
            'has_active_items' => $activeItems->isNotEmpty(),
            'payment_note' => 'Parcelas de cartão consideradas pagas quando a fatura está quitada. Demais parcelas usam a data de vencimento como referência.',
        ];
    }

    /**
     * @param  array<int, int|string>  $networkIds
     */
    private function installmentPurchasesQuery(array $networkIds, array $filters)
    {
        return Transaction::query()
            ->with([
                'installments' => fn ($query) => $query->orderBy('installment_number'),
                'installments.statement',
                'category',
                'creditCard',
                'users',
            ])
            ->whereNotNull('installment_total')
            ->where('installment_total', '>=', 2)
            ->whereHas('users', fn ($query) => $query->whereIn('users.id', $networkIds))
            ->when(
                ! empty($filters['credit_card_id']),
                fn ($query) => $query->where('credit_card_id', (int) $filters['credit_card_id'])
            )
            ->when(
                ! empty($filters['category_id']),
                fn ($query) => $query->where('category_id', (int) $filters['category_id'])
            )
            ->when(
                ! empty($filters['purchase_from']),
                fn ($query) => $query->whereDate('transaction_date', '>=', $filters['purchase_from'])
            )
            ->when(
                ! empty($filters['purchase_to']),
                fn ($query) => $query->whereDate('transaction_date', '<=', $filters['purchase_to'])
            )
            ->orderByDesc('transaction_date')
            ->orderBy('description');
    }

    /**
     * @param  array<int, int|string>  $networkIds
     */
    private function isWithinNetwork(Transaction $transaction, array $networkIds): bool
    {
        return $transaction->users->every(
            fn (User $participant) => in_array($participant->id, $networkIds, true)
        );
    }

    private function mapPurchase(Transaction $transaction, Carbon $today): array
    {
        $installments = $transaction->installments->values();
        $amounts = $this->resolveInstallmentAmounts($transaction, $installments);
        $totalInstallments = (int) $transaction->installment_total;
        $implicitPaid = max(0, ((int) ($transaction->installment_number ?: 1)) - 1);

        $paidInRecords = 0;
        $remainingAmount = 0.0;
        $nextInstallment = null;

        foreach ($installments as $index => $installment) {
            $amount = $amounts[$index];
            $isPaid = $this->isInstallmentPaid($installment, $today);

            if ($isPaid) {
                $paidInRecords++;
            } else {
                $remainingAmount += $amount;

                if ($nextInstallment === null) {
                    $nextInstallment = [
                        'installment_number' => (int) $installment->installment_number,
                        'amount' => round($amount, 2),
                        'due_date' => $installment->due_date?->format('Y-m-d'),
                    ];
                }
            }
        }

        $paidInstallments = min($totalInstallments, $implicitPaid + $paidInRecords);
        $remainingInstallments = max(0, $totalInstallments - $paidInstallments);
        $currentInstallment = $remainingInstallments > 0
            ? min($totalInstallments, $paidInstallments + 1)
            : $totalInstallments;

        $nominalInstallment = $totalInstallments > 0
            ? round((float) $transaction->total_amount / $totalInstallments, 2)
            : round((float) $transaction->amount, 2);

        $status = $this->resolveStatus($remainingInstallments, $nextInstallment, $today);

        return [
            'transaction_id' => $transaction->id,
            'description' => $transaction->description,
            'purchase_date' => $transaction->transaction_date?->format('Y-m-d'),
            'total_amount' => round((float) $transaction->total_amount, 2),
            'installment_amount' => $nominalInstallment,
            'current_installment' => $currentInstallment,
            'total_installments' => $totalInstallments,
            'remaining_installments' => $remainingInstallments,
            'remaining_amount' => round($remainingAmount, 2),
            'next_due_date' => $nextInstallment['due_date'] ?? null,
            'next_installment_amount' => $nextInstallment['amount'] ?? null,
            'card_name' => $transaction->creditCard?->name,
            'category_name' => $transaction->category?->name,
            'status' => $status['key'],
            'status_label' => $status['label'],
        ];
    }

    /**
     * @return array<int, float>
     */
    private function resolveInstallmentAmounts(Transaction $transaction, Collection $installments): array
    {
        if ($installments->isEmpty()) {
            return [];
        }

        $totalAmount = round((float) $transaction->total_amount, 2);
        $regularTotal = round((float) $installments->slice(0, -1)->sum('amount'), 2);
        $lastAmount = round($totalAmount - $regularTotal, 2);

        return $installments
            ->values()
            ->map(fn (TransactionInstallment $installment, int $index) => $index === $installments->count() - 1
                ? $lastAmount
                : round((float) $installment->amount, 2))
            ->all();
    }

    private function isInstallmentPaid(TransactionInstallment $installment, Carbon $today): bool
    {
        if ($installment->credit_card_statement_id && $installment->statement?->status === 'paid') {
            return true;
        }

        if (! $installment->due_date) {
            return false;
        }

        return $installment->due_date->copy()->startOfDay()->lt($today);
    }

    /**
     * @param  array<string, mixed>|null  $nextInstallment
     * @return array{key: string, label: string}
     */
    private function resolveStatus(int $remainingInstallments, ?array $nextInstallment, Carbon $today): array
    {
        if ($remainingInstallments === 0) {
            return ['key' => 'completed', 'label' => 'Quitada'];
        }

        $isLastInstallment = $remainingInstallments === 1;
        $dueWithinThirtyDays = false;

        if (! empty($nextInstallment['due_date'])) {
            $dueDate = Carbon::parse($nextInstallment['due_date'])->startOfDay();
            $dueWithinThirtyDays = $dueDate->lte($today->copy()->addDays(30));
        }

        if ($isLastInstallment || $dueWithinThirtyDays) {
            return ['key' => 'ending', 'label' => 'Finalizando'];
        }

        return ['key' => 'active', 'label' => 'Em andamento'];
    }

    private function matchesStatusFilter(array $item, string $statusFilter): bool
    {
        return match ($statusFilter) {
            'completed' => $item['status'] === 'completed',
            'all' => true,
            default => $item['status'] !== 'completed',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $activeItems
     * @param  array<int, array<string, mixed>>  $items
     */
    private function buildSummary(Collection $activeItems, array $items, Carbon $today): array
    {
        $next = $activeItems
            ->filter(fn (array $item) => ! empty($item['next_due_date']))
            ->sortBy('next_due_date')
            ->first();

        return [
            'active_count' => $activeItems->count(),
            'remaining_total' => round($activeItems->sum('remaining_amount'), 2),
            'next_installment' => $next ? [
                'description' => $next['description'],
                'amount' => $next['next_installment_amount'],
                'due_date' => $next['next_due_date'],
            ] : null,
            'ending_soon_count' => $activeItems
                ->filter(fn (array $item) => $item['status'] === 'ending')
                ->count(),
            'visible_count' => count($items),
        ];
    }

    /**
     * @param  array<int, int|string>  $networkIds
     */
    private function buildFilterOptions(array $networkIds): array
    {
        $transactions = Transaction::query()
            ->with(['category', 'creditCard'])
            ->whereNotNull('installment_total')
            ->where('installment_total', '>=', 2)
            ->whereHas('users', fn ($query) => $query->whereIn('users.id', $networkIds))
            ->get()
            ->filter(fn (Transaction $transaction) => $this->isWithinNetwork($transaction, $networkIds));

        $cards = $transactions
            ->filter(fn (Transaction $transaction) => $transaction->credit_card_id)
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->credit_card_id,
                'name' => $transaction->creditCard?->name,
            ])
            ->filter(fn (array $card) => $card['id'] && $card['name'])
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();

        $categories = $transactions
            ->filter(fn (Transaction $transaction) => $transaction->category_id)
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->category_id,
                'name' => $transaction->category?->name,
            ])
            ->filter(fn (array $category) => $category['id'] && $category['name'])
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();

        return [
            'credit_cards' => $cards,
            'categories' => $categories,
        ];
    }
}
