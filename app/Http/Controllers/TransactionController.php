<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Repositories\TransactionRepositoryInterface;
use App\Services\GenerateInstallmentsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Exists;

class TransactionController extends Controller
{

    public function __construct(
        private TransactionRepositoryInterface $transactions
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        try {
            $perPage = min($request->integer('per_page', default: 15), 100);

            $filters = [
                'month'             => $request->query('month'),
                'year'              => $request->query('year'),
                'user_id'           => $request->query('user_id'),
                'category_id'       => $request->query('category_id'),
                'type_id'           => $request->query('type_id'),
                'payment_method_id' => $request->query('payment_method_id'),
            ];

            $transactions = $this->transactions->getPaginatedTransactions($perPage, $filters);
            return TransactionResource::collection($transactions);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve transactions',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(StoreTransactionRequest $request)
    {
        try {

            $data = $request->validated();
            $userIds = $data['user_ids'];
            unset($data['user_ids']);

            $transaction = $this->transactions->createTransaction($data, $userIds);

            if ($transaction->credit_card_id) {
                $service->generate($transaction);
            }
            return (new TransactionResource($transaction))
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to create transaction',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $transaction = $this->transactions->findTransactionById($id);

            if (!$transaction) {
                return response()->json([
                    'error' => 'Transaction not found'
                ], 404);
            }

            return (new TransactionResource($transaction))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve transaction',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTransactionRequest $request, string $id)
    {
        try {

            $data = $request->validated();

            $userIds = $data['user_ids'] ?? null;
            unset($data['user_ids']);

            $transaction = $this->transactions->updateTransaction($id, $data, $userIds);

            if (!$transaction) {
                return response()->json([
                    'error' => 'Transaction not found'
                ], 404);
            }

            return (new TransactionResource($transaction))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to create transaction',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $deleted = $this->transactions->deleteTransaction($id);

            if (!$deleted) {
                return response()->json([
                    'error' => 'Transaction not found'
                ], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete transaction',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
