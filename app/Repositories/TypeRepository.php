<?php

namespace App\Repositories;

use App\Models\Type;
use Illuminate\Support\Collection;

class TypeRepository implements TypeRepositoryInterface
{
    public function getAll(): Collection
    {
        return Type::orderBy('name')->get();
    }

    public function findById(int $id): ?Type
    {
        return Type::find($id);
    }

    public function create(array $data): Type
    {
        return Type::create($data);
    }

    public function update(int $id, array $data): ?Type
    {
        $type = Type::find($id);

        if (! $type) {
            return null;
        }

        $type->update($data);

        return $type;
    }

    public function delete(int $id): bool
    {
        $type = Type::find($id);

        if (! $type) {
            return false;
        }

        $type->delete();

        return true;
    }
}
