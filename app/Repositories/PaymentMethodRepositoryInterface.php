<?php

namespace App\Repositories;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

interface PaymentMethodRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(int $id): ?PaymentMethod;

    public function create(array $data): PaymentMethod;

    public function update(int $id, array $data): ?PaymentMethod;

    public function delete(int $id): bool;
}
