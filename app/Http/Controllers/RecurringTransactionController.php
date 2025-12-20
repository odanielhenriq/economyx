<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Repositories\RecurringTransactionRepositoryInterface;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function __construct(
        private RecurringTransactionRepositoryInterface $recurringTransactions
    ) {}

    public function index(Request $request)
    {
        try {
            $templates = $this->recurringTransactions->getAll();

            return RecurringTransactionResource::collection($templates);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve recurring templates',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(RecurringTransactionRequest $request)
    {
        try {
            $data = $request->validated();

            $userIds = $data['user_ids'];
            unset($data['user_ids']);

            $template = $this->recurringTransactions->create($data, $userIds);

            return (new RecurringTransactionResource($template))
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to create recurring template',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $template = $this->recurringTransactions->findById((int) $id);

            if (! $template) {
                return response()->json([
                    'error' => 'Recurring template not found',
                ], 404);
            }

            return (new RecurringTransactionResource($template))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve recurring template',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(RecurringTransactionRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $userIds = $data['user_ids'] ?? null;
            unset($data['user_ids']);

            $template = $this->recurringTransactions->update((int) $id, $data, $userIds);

            if (! $template) {
                return response()->json([
                    'error' => 'Recurring template not found',
                ], 404);
            }

            return (new RecurringTransactionResource($template))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to update recurring template',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $deleted = $this->recurringTransactions->delete((int) $id);

            if (! $deleted) {
                return response()->json([
                    'error' => 'Recurring template not found',
                ], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete recurring template',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
