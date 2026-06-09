<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NetworkScope
{
    /**
     * @return list<int>
     */
    public static function ids(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return $user->networkUsers()->pluck('id')->all();
    }

    public static function contains(?User $viewer, int $targetUserId): bool
    {
        if (! $viewer) {
            return false;
        }

        return in_array($targetUserId, self::ids($viewer), true);
    }

    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public static function filterUserIds(?User $viewer, array $userIds): array
    {
        $networkIds = self::ids($viewer);

        return array_values(array_intersect($userIds, $networkIds));
    }

    public static function applyTransactionScope(Builder $query, User $viewer): Builder
    {
        $networkIds = self::ids($viewer);

        return $query->whereHas('users', function ($q) use ($networkIds) {
            $q->whereIn('users.id', $networkIds);
        });
    }

    public static function applyRecurringScope(Builder $query, User $viewer): Builder
    {
        $networkIds = self::ids($viewer);

        return $query->whereHas('users', function ($q) use ($networkIds) {
            $q->whereIn('users.id', $networkIds);
        });
    }

    public static function userCanAccessCreditCard(User $viewer, int $creditCardId): bool
    {
        return \App\Models\CreditCard::query()
            ->where('id', $creditCardId)
            ->where(function ($q) use ($viewer) {
                $q->where('owner_user_id', $viewer->id)
                    ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $viewer->id));
            })
            ->exists();
    }
}
