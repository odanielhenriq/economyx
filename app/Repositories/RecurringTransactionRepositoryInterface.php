<?php

namespace App\Repositories;

use App\Models\RecurringTransaction;
use Illuminate\Support\Collection;

interface RecurringTransactionRepositoryInterface
{
    public function getForUser(\App\Models\User $user): Collection;

    public function getAll(): Collection;

    public function findById(int $id): ?RecurringTransaction;

    public function findForUser(int $id, \App\Models\User $user): ?RecurringTransaction;

    public function create(array $data, array $userIds): RecurringTransaction;

    public function update(int $id, array $data, ?array $userIds = null): ?RecurringTransaction;

    public function delete(int $id): bool;
}
