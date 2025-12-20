<?php

namespace App\Repositories;

use App\Models\Type;
use Illuminate\Support\Collection;

interface TypeRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(int $id): ?Type;

    public function create(array $data): Type;

    public function update(int $id, array $data): ?Type;

    public function delete(int $id): bool;
}
