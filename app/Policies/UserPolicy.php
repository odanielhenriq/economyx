<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDev();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isDev();
    }

    public function impersonate(User $user, User $model): bool
    {
        if (! $user->isDev()) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $model->role !== UserRole::Dev;
    }
}
