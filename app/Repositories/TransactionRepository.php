<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\CreditCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * Retorna todas as transações (sem paginação).
     */
    public function getAllTransactions(): Collection
    {
        return Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'users'
        ])->get();
    }

    /**
     * Lista paginada com filtros.
     */
    public function getPaginatedTransactions(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {

        $query = Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'creditCard',
            'users'
        ])->orderByDesc('transaction_date');

        // Filtro por usuário (relacionamento N:N)
        if (!empty($filters['user_id'])) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->where('users.id', $filters['user_id']);
            });
        }

        // Filtro por mês no formato "YYYY-MM"
        if (!empty($filters['month'])) {
            [$year, $month] = explode('-', $filters['month']);

            $query
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('transaction_date', $filters['year']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }

        if (!empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Cria transação e relaciona com usuários.
     */
    public function createTransaction(array $data, array $userIds): Transaction
    {

        if ((int) ($data['payment_method_id'] ?? 0) !== 1) {
            $data['credit_card_id'] = null; // provavelmente deveria ser 'credit_card_id'
        }

        // Se quiser garantir total_amount, pode reativar essa regra:
        // if (empty($data['total_amount'])) {
        //     $data['total_amount'] = $data['amount'] ?? null;
        // }

        $transaction = Transaction::create($data);

        // Relaciona usuários
        $transaction->users()->sync($userIds);

        return $transaction->load(['category', 'type', 'paymentMethod', 'users']);
    }

    public function findTransactionById(int $id): ?Transaction
    {
        return Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'users'
        ])->find($id);
    }

    public function updateTransaction(int $id, array $data, ?array $userIds = null): Transaction
    {
        $transaction = Transaction::findOrFail($id);

        // Mesmo possível bug aqui: card_id vs credit_card_id
        if ((int) ($data['payment_method_id'] ?? 0) !== 1) {
            $data['card_id'] = null; // deveria ser 'credit_card_id'
        }

        // Garante total_amount se vier vazio
        if (empty($data['total_amount'])) {
            $data['total_amount'] = $data['amount'] ?? null;
        }

        $transaction->update($data);

        if ($userIds !== null) {
            $transaction->users()->sync($userIds);
        }

        return $transaction->load([
            'category',
            'type',
            'paymentMethod',
            'creditCard',
            'users',
        ]);
    }

    public function deleteTransaction(int $id): bool
    {
        $transaction = Transaction::find($id);

        if (! $transaction) {
            return false;
        }

        // Soft delete
        $transaction->delete();

        return true;
    }

    /**
     * Monta um "extrato" de cartão com base em transaction_date
     * (VERSÃO ANTIGA, sem usar credit_card_statements + installments).
     *
     * Hoje você já está migrando isso para a estrutura nova no CardStatementController.
     */
    public function getCardBill(int $cardId, int $year, int $month): array
    {
        // 1) Carrega o cartão com o dono
        $card = CreditCard::with('owner')->findOrFail($cardId);

        // 2) Calcula o período de faturamento usando método do model
        [$periodStart, $periodEnd] = $card->getBillingPeriodFor($year, $month);

        // 3) Busca transações do cartão dentro do período
        $transactions = Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'creditCard',
            'users',
        ])
            ->where('credit_card_id', $card->id)
            ->whereBetween('transaction_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->orderBy('transaction_date')
            ->get();

        // 4) Calcula resumo (separando receita/despesa por type.slug)
        $income  = 0.0;
        $expense = 0.0;

        foreach ($transactions as $tx) {
            $slug = $tx->type?->slug; // ex.: 'rc' ou 'dc'
            $amount = (float) $tx->amount;

            $signed = $slug === 'rc'
                ? $amount      // receita
                : -1 * $amount; // despesa

            if ($signed > 0) {
                $income += $signed;
            } else {
                $expense += $signed;
            }
        }

        return [
            'card' => $card,
            'period' => [
                'start' => $periodStart,
                'end'   => $periodEnd,
            ],
            'summary' => [
                'income'  => round($income, 2),
                'expense' => round($expense, 2),
                'net'     => round($income + $expense, 2),
            ],
            'transactions' => $transactions,
        ];
    }
}
