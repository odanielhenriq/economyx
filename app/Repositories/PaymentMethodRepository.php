<?php

namespace App\Repositories;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

class PaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    public function getAll(): Collection
    {
        return PaymentMethod::orderBy('name')->get();
    }

    public function findById(int $id): ?PaymentMethod
    {
        return PaymentMethod::find($id);
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::create($data);
    }

    public function update(int $id, array $data): ?PaymentMethod
    {
        $paymentMethod = PaymentMethod::find($id);

        if (! $paymentMethod) {
            return null;
        }

        $paymentMethod->update($data);

        return $paymentMethod;
    }

    public function delete(int $id): bool
    {
        $paymentMethod = PaymentMethod::find($id);

        if (! $paymentMethod) {
            return false;
        }

        $paymentMethod->delete();

        return true;
    }
}
