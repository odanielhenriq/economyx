<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function getAllTransactions(): Collection
    {
        return Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'users'
        ])->get();
    }

    public function getPaginatedTransactions(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {

        $query = Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'creditCard',
            'users'
        ])->orderByDesc('transaction_date');

        if (!empty($filters['user_id'])) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->where('users.id', $filters['user_id']);
            });
        }

        if (!empty($filters['month'])) {
            // Esperando algo tipo "2025-12"
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

    public function createTransaction(array $data, array $userIds): Transaction
    {

        if ((int) ($data['payment_method_id'] ?? 0) !== 1) {
            $data['card_id'] = null;
        }

        $transaction = Transaction::create($data);

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

        if ((int) ($data['payment_method_id'] ?? 0) !== 1) {
            $data['card_id'] = null;
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

        $transaction->delete(); // Soft delete

        return true;
    }
}
