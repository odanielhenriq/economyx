<?php

namespace App\Policies;

use App\Models\CreditCard;
use App\Models\User;
use App\Support\NetworkScope;

class CreditCardPolicy
{
    public function view(User $user, CreditCard $creditCard): bool
    {
        return NetworkScope::userCanAccessCreditCard($user, $creditCard->id);
    }

    public function update(User $user, CreditCard $creditCard): bool
    {
        return NetworkScope::userCanAccessCreditCard($user, $creditCard->id);
    }

    public function delete(User $user, CreditCard $creditCard): bool
    {
        return NetworkScope::userCanAccessCreditCard($user, $creditCard->id);
    }
}
