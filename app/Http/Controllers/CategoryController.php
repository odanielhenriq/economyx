<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepositoryInterface;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepositoryInterface $categories
    ) {}

    public function index()
    {
        try {
            $categories = $this->categories->getAll();

            return CategoryResource::collection($categories);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve categories',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(CategoryRequest $request)
    {
        try {
            $data = $request->validated();

            $category = $this->categories->create($data);

            return (new CategoryResource($category))
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to create category',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $category = $this->categories->findById((int) $id);

            if (! $category) {
                return response()->json([
                    'error' => 'Category not found',
                ], 404);
            }

            return (new CategoryResource($category))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve category',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(CategoryRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $category = $this->categories->update((int) $id, $data);

            return (new CategoryResource($category))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to update category',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $deleted = $this->categories->delete((int) $id);

            if (! $deleted) {
                return response()->json([
                    'error' => 'Category not found',
                ], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete category',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
