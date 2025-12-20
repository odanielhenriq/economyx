<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'alias' => $this->alias,
            'closing_day' => $this->closing_day,
            'due_day' => $this->due_day,
            'limit' => $this->limit,
            'owner_user_id' => $this->owner_user_id,
            'owner_name' => $this->owner_name,
            'is_shared' => (bool) $this->is_shared,
            'owner' => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ] : null,
            'users' => $this->users?->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]),
        ];
    }
}
