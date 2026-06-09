<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Repositories\RecurringTransactionRepositoryInterface;
use App\Support\NetworkScope;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function __construct(
        private RecurringTransactionRepositoryInterface $recurringTransactions
    ) {}

    public function index(Request $request)
    {
        try {
            $templates = $this->recurringTransactions->getForUser($request->user());

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

            $userIds = NetworkScope::filterUserIds($request->user(), $data['user_ids']);
            if ($userIds === []) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
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

    public function show(Request $request, string $id)
    {
        try {
            $template = $this->recurringTransactions->findForUser((int) $id, $request->user());

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
            $existing = $this->recurringTransactions->findForUser((int) $id, $request->user());

            if (! $existing) {
                return response()->json([
                    'error' => 'Recurring template not found',
                ], 404);
            }

            $data = $request->validated();

            $userIds = null;
            if (array_key_exists('user_ids', $data)) {
                $userIds = NetworkScope::filterUserIds($request->user(), $data['user_ids']);
                if ($userIds === []) {
                    return response()->json(['error' => 'Forbidden'], 403);
                }
            }
            unset($data['user_ids']);

            $template = $this->recurringTransactions->update((int) $id, $data, $userIds);

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

    public function destroy(Request $request, string $id)
    {
        try {
            $existing = $this->recurringTransactions->findForUser((int) $id, $request->user());

            if (! $existing) {
                return response()->json([
                    'error' => 'Recurring template not found',
                ], 404);
            }

            $this->recurringTransactions->delete((int) $id);

            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete recurring template',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
