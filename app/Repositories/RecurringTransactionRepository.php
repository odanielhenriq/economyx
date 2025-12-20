<?php

namespace App\Repositories;

use App\Models\RecurringTransaction;
use Illuminate\Support\Collection;

class RecurringTransactionRepository implements RecurringTransactionRepositoryInterface
{
    public function getAll(): Collection
    {
        return RecurringTransaction::with([
            'category',
            'type',
            'paymentMethod',
            'creditCard.owner',
            'users',
        ])
            ->orderBy('description')
            ->get();
    }

    public function findById(int $id): ?RecurringTransaction
    {
        return RecurringTransaction::with([
            'category',
            'type',
            'paymentMethod',
            'creditCard.owner',
            'users',
        ])->find($id);
    }

    public function create(array $data, array $userIds): RecurringTransaction
    {
        $recurring = RecurringTransaction::create($data);
        $recurring->users()->sync($userIds);

        return $recurring->load([
            'category',
            'type',
            'paymentMethod',
            'creditCard.owner',
            'users',
        ]);
    }

    public function update(int $id, array $data, ?array $userIds = null): ?RecurringTransaction
    {
        $recurring = RecurringTransaction::find($id);

        if (! $recurring) {
            return null;
        }

        $recurring->update($data);

        if ($userIds !== null) {
            $recurring->users()->sync($userIds);
        }

        return $recurring->load([
            'category',
            'type',
            'paymentMethod',
            'creditCard.owner',
            'users',
        ]);
    }

    public function delete(int $id): bool
    {
        $recurring = RecurringTransaction::find($id);

        if (! $recurring) {
            return false;
        }

        $recurring->delete();

        return true;
    }
}
