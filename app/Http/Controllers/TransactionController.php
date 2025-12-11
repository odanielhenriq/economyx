<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Http\Request;

class TransactionController extends Controller
{

    public function __construct(
        // Repository de transações (injeção de dependência)
        private TransactionRepositoryInterface $transactions
    ) {}

    /**
     * Lista paginada de transações (API JSON).
     * Aceita filtros por mês, ano, usuário, categoria, tipo, método de pagamento.
     */
    public function index(Request $request)
    {

        try {
            // Limita o per_page a no máximo 100
            $perPage = min($request->integer('per_page', default: 15), 100);

            // Monta array de filtros a partir da query string
            $filters = [
                'month'             => $request->query('month'),
                'year'              => $request->query('year'),
                'user_id'           => $request->query('user_id'),
                'category_id'       => $request->query('category_id'),
                'type_id'           => $request->query('type_id'),
                'payment_method_id' => $request->query('payment_method_id'),
            ];

            // Pede pro repository fazer a query filtrada e paginada
            $transactions = $this->transactions->getPaginatedTransactions($perPage, $filters);

            // Retorna coleção de TransactionResource (formato padronizado da API)
            return TransactionResource::collection($transactions);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve transactions',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova transação via API.
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            // Valida dados conforme StoreTransactionRequest
            $data = $request->validated();

            // Separa os usuários que vão dividir a transação
            $userIds = $data['user_ids'];
            unset($data['user_ids']);

            // Cria a transação via repository
            $transaction = $this->transactions->createTransaction($data, $userIds);

            // IMPORTANTE: as parcelas são geradas no `booted()` do model Transaction
            // quando a transação é criada (para cartão/empréstimo)

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
     * Mostra detalhes de uma transação específica.
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
     * Atualiza uma transação via API.
     * (Por enquanto, você NÃO está recalculando parcelas ao editar)
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
     * Remove (soft delete) uma transação.
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

            // HTTP 204 = no content
            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete transaction',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
