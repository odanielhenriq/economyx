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

    public function getPaginatedTransactions(int $perPage = 1): LengthAwarePaginator
    {
        return Transaction::with([
            'category',
            'type',
            'paymentMethod',
            'users'
        ])->orderByDesc('transaction_date')->paginate($perPage);
    }

    public function createTransaction(array $data, array $userIds): Transaction
    {
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
