<?php

namespace App\Repositories;

use App\Models\CreditCard;
use Illuminate\Support\Collection;

class CreditCardRepository implements CreditCardRepositoryInterface
{
    public function getAll(): Collection
    {
        return CreditCard::with(['owner', 'users'])
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?CreditCard
    {
        return CreditCard::with(['owner', 'users'])->find($id);
    }

    public function create(array $data, array $sharedUserIds = []): CreditCard
    {
        $creditCard = CreditCard::create($data);

        if (! empty($sharedUserIds)) {
            $creditCard->users()->sync($sharedUserIds);
        }

        return $creditCard->load(['owner', 'users']);
    }

    public function update(int $id, array $data, ?array $sharedUserIds = null): ?CreditCard
    {
        $creditCard = CreditCard::find($id);

        if (! $creditCard) {
            return null;
        }

        $creditCard->update($data);

        if ($sharedUserIds !== null) {
            $creditCard->users()->sync($sharedUserIds);
        }

        return $creditCard->load(['owner', 'users']);
    }

    public function delete(int $id): bool
    {
        $creditCard = CreditCard::find($id);

        if (! $creditCard) {
            return false;
        }

        $creditCard->delete();

        return true;
    }
}
