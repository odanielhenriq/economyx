<?php

namespace App\Policies;

use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use App\Support\NetworkScope;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $this->inNetwork($user, $transaction);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $this->inNetwork($user, $transaction);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->inNetwork($user, $transaction);
    }

    private function inNetwork(User $user, Transaction $transaction): bool
    {
        $networkIds = NetworkScope::ids($user);

        return $transaction->users()->whereIn('users.id', $networkIds)->exists();
    }
}
