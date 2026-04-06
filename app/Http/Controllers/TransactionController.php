<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Controller responsável por gerenciar transações via API.
 *
 * Este controller lida com todas as operações CRUD de transações:
 * - Listar transações (com filtros e paginação)
 * - Criar novas transações
 * - Atualizar transações existentes
 * - Excluir transações (soft delete)
 *
 * Fluxo de criação:
 * 1. Valida dados (StoreTransactionRequest)
 * 2. Cria transação via Repository
 * 3. Repository dispara evento Transaction::created
 * 4. Evento gera parcelas automaticamente (se aplicável)
 *
 * @see App\Repositories\TransactionRepository
 * @see App\Models\Transaction
 * @see App\Services\GenerateInstallmentsService
 */
class TransactionController extends Controller
{

    /**
     * Injeção de dependência do repository de transações.
     *
     * @param \App\Repositories\TransactionRepositoryInterface $transactions
     */
    public function __construct(
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
                'search'            => $request->query('search'),
            ];

            // Pede pro repository fazer a query filtrada e paginada
            $transactions = $this->transactions->getPaginatedTransactions($perPage, $filters);

            // Totais do período completo (não só da página)
            $periodTotals = $this->transactions->getPeriodTotals($filters);

            // Serializa a coleção paginada e injeta totais no meta
            $responseData = TransactionResource::collection($transactions)->response()->getData(true);
            $responseData['meta']['totals'] = $periodTotals;

            return response()->json($responseData);
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

            // Converte credit_card_id vazio para null
            if (isset($data['credit_card_id']) && $data['credit_card_id'] === '') {
                $data['credit_card_id'] = null;
            }

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

            $editScope = $data['edit_scope'] ?? null;
            unset($data['edit_scope']);

            $transactionModel = $this->transactions->findTransactionById((int) $id);

            if (! $transactionModel) {
                return response()->json([
                    'error' => 'Transaction not found',
                ], 404);
            }

            if ($transactionModel->recurring_transaction_id) {
                $editScope = $editScope ?: 'single';
            }

            if ($transactionModel->recurring_transaction_id && $editScope === 'template') {
                $template = RecurringTransaction::find($transactionModel->recurring_transaction_id);

                if ($template) {
                    $templateData = Arr::only($data, [
                        'description',
                        'amount',
                        'total_amount',
                        'category_id',
                        'type_id',
                        'payment_method_id',
                        'credit_card_id',
                    ]);

                    $template->update($templateData);

                    if ($userIds !== null) {
                        $template->users()->sync($userIds);
                    }
                }

                $transaction = $this->transactions->updateTransaction($id, $data, $userIds);
            } else {
                if ($transactionModel->recurring_transaction_id) {
                    $data['recurring_transaction_id'] = null;
                }

                $transaction = $this->transactions->updateTransaction($id, $data, $userIds);
            }

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
     * Retorna todas as parcelas de uma transação parcelada.
     */
    public function installments(string $id)
    {
        try {
            $transaction = $this->transactions->findTransactionById((int) $id);

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            $installments = $transaction->installments()
                ->orderBy('installment_number')
                ->get()
                ->map(fn($i) => [
                    'id'                 => $i->id,
                    'installment_number' => $i->installment_number,
                    'total'              => $i->installment_total,
                    'due_date'           => $i->due_date?->format('Y-m-d'),
                    'amount'             => $i->amount,
                    'is_past'            => $i->due_date && $i->due_date->isPast() && !$i->due_date->isCurrentMonth(),
                    'is_current'         => $i->due_date && $i->due_date->isCurrentMonth(),
                ]);

            return response()->json([
                'description'  => $transaction->description,
                'installments' => $installments,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error'   => 'Failed to retrieve installments',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplica uma transação existente como uma transação avulsa com data de hoje.
     */
    public function duplicate(string $id)
    {
        try {
            $transaction = Transaction::with(['users', 'category', 'type', 'paymentMethod'])->find($id);

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            $nova = $transaction->replicate();
            $nova->due_date                 = now()->format('Y-m-d');
            $nova->transaction_date         = now()->format('Y-m-d');
            $nova->recurring_transaction_id = null;
            $nova->installment_number       = null;
            $nova->installment_total        = null;
            $nova->total_amount             = $transaction->amount;
            $nova->save();

            $nova->users()->sync($transaction->users->pluck('id')->all());

            return response()->json([
                'message'     => 'Transação duplicada com sucesso.',
                'transaction' => new TransactionResource(
                    $nova->load(['category', 'type', 'paymentMethod', 'users'])
                ),
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error'   => 'Failed to duplicate transaction',
                'message' => $th->getMessage(),
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

            if (! $deleted) {
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
