<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAll(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): ?Category
    {
        $category = Category::find($id);

        if (! $category) {
            return null;
        }

        $category->update($data);

        return $category;
    }

    public function delete(int $id): bool
    {
        $category = Category::find($id);

        if (! $category) {
            return false;
        }

        $category->delete();

        return true;
    }
}
