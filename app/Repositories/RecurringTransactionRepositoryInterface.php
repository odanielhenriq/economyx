<?php

namespace App\Repositories;

use App\Models\RecurringTransaction;
use Illuminate\Support\Collection;

interface RecurringTransactionRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(int $id): ?RecurringTransaction;

    public function create(array $data, array $userIds): RecurringTransaction;

    public function update(int $id, array $data, ?array $userIds = null): ?RecurringTransaction;

    public function delete(int $id): bool;
}
