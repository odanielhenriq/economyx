<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface
{
    public function getAllTransactions(): Collection;

    public function getPaginatedTransactions(int $perPage = 15): LengthAwarePaginator;

    public function createTransaction(array $data, array $userIds): Transaction;

    public function findTransactionById(int $id): ?Transaction;

    public function updateTransaction(int $id, array $data, ?array $userIds = null): Transaction;

    public function deleteTransaction(int $id): bool;
}
