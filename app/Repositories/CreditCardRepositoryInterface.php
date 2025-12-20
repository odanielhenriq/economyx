<?php

namespace App\Repositories;

use App\Models\CreditCard;
use Illuminate\Support\Collection;

interface CreditCardRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(int $id): ?CreditCard;

    public function create(array $data, array $sharedUserIds = []): CreditCard;

    public function update(int $id, array $data, ?array $sharedUserIds = null): ?CreditCard;

    public function delete(int $id): bool;
}
