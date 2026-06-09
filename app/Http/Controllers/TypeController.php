<?php

namespace App\Http\Controllers;

use App\Http\Requests\TypeRequest;
use App\Http\Resources\TypeResource;
use App\Repositories\TypeRepositoryInterface;
use App\Support\ReferenceSlugs;

class TypeController extends Controller
{
    public function __construct(
        private TypeRepositoryInterface $types
    ) {}

    public function index()
    {
        try {
            $types = $this->types->getAll();

            return TypeResource::collection($types);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve types',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(TypeRequest $request)
    {
        try {
            $data = $request->validated();

            $type = $this->types->create($data);

            return (new TypeResource($type))
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to create type',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $type = $this->types->findById((int) $id);

            if (! $type) {
                return response()->json([
                    'error' => 'Type not found',
                ], 404);
            }

            return (new TypeResource($type))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve type',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(TypeRequest $request, string $id)
    {
        try {
            $existing = $this->types->findById((int) $id);

            if (! $existing) {
                return response()->json(['error' => 'Type not found'], 404);
            }

            if (ReferenceSlugs::isSystemTypeSlug($existing->slug)) {
                return response()->json(['error' => 'Este tipo é usado pelo sistema e não pode ser alterado.'], 403);
            }

            $data = $request->validated();

            $type = $this->types->update((int) $id, $data);

            if (! $type) {
                return response()->json([
                    'error' => 'Type not found',
                ], 404);
            }

            return (new TypeResource($type))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to update type',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $existing = $this->types->findById((int) $id);

            if (! $existing) {
                return response()->json(['error' => 'Type not found'], 404);
            }

            if (ReferenceSlugs::isSystemTypeSlug($existing->slug)) {
                return response()->json(['error' => 'Este tipo é usado pelo sistema e não pode ser excluído.'], 403);
            }

            $deleted = $this->types->delete((int) $id);

            if (! $deleted) {
                return response()->json([
                    'error' => 'Type not found',
                ], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete type',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
