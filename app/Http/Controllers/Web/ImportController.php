<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Type;
use App\Repositories\TransactionRepositoryInterface;
use App\Services\ImportStatementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private ImportStatementService $importService,
        private TransactionRepositoryInterface $transactions,
    ) {}

    /**
     * Analisa o extrato via IA e devolve as transações sugeridas (sem salvar).
     */
    public function analyze(Request $request): JsonResponse
    {
        if (! config('services.anthropic.key')) {
            return response()->json([
                'error' => 'Importação via IA não configurada. Defina ANTHROPIC_API_KEY no .env ou lance transações manualmente.',
            ], 503);
        }

        $request->validate([
            'content'        => 'required|string|min:10',
            'credit_card_id' => 'nullable|integer|exists:credit_cards,id',
        ]);

        $categories = Category::orderBy('name')->get();

        try {
            $transactions = $this->importService->parse(
                $request->string('content')->value(),
                $categories
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['transactions' => $transactions]);
    }

    /**
     * Persiste as transações revisadas pelo usuário.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'transactions'               => 'required|array|min:1',
            'transactions.*.date'        => 'required|date',
            'transactions.*.description' => 'required|string|max:255',
            'transactions.*.amount'      => 'required|numeric|min:0.01',
            'transactions.*.type'        => 'required|in:income,expense',
            'transactions.*.category_id'        => 'nullable|integer|exists:categories,id',
            'transactions.*.installment_number'  => 'nullable|integer|min:1',
            'transactions.*.installment_total'   => 'nullable|integer|min:1',
            'credit_card_id'                     => 'nullable|integer|exists:credit_cards,id',
            'payment_method_id'                  => 'required|integer|exists:payment_methods,id',
            'user_ids'                           => 'nullable|array',
            'user_ids.*'                         => 'integer|exists:users,id',
        ]);

        try {
            $typeIncome = Type::where('slug', 'rc')->first()
                ?? throw new \RuntimeException('Tipo de receita não encontrado. Execute os seeders.');

            $typeExpense = Type::where('slug', 'dc')->first()
                ?? throw new \RuntimeException('Tipo de despesa não encontrado. Execute os seeders.');
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $userId   = auth()->id();
        $userIds  = $request->input('user_ids', [$userId]);
        if (! in_array($userId, $userIds)) {
            $userIds[] = $userId;
        }

        $imported = 0;
        $failed   = 0;
        $errors   = [];

        foreach ($request->input('transactions') as $index => $tx) {
            try {
                $typeId = $tx['type'] === 'income' ? $typeIncome->id : $typeExpense->id;

                $data = [
                    'description'        => $tx['description'],
                    'amount'             => $tx['amount'],
                    'total_amount'       => $tx['amount'],
                    'transaction_date'   => $tx['date'],
                    'category_id'        => $tx['category_id'] ?: null,
                    'type_id'            => $typeId,
                    'payment_method_id'  => $request->integer('payment_method_id'),
                    'credit_card_id'     => $request->input('credit_card_id') ?: null,
                    'installment_number' => $tx['installment_number'] ?? null,
                    'installment_total'  => $tx['installment_total'] ?? null,
                ];

                $this->transactions->createTransaction($data, $userIds, false);
                $imported++;

            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'index'       => $index,
                    'description' => $tx['description'] ?? '?',
                    'error'       => $e->getMessage(),
                ];
            }
        }

        $message = $imported > 0
            ? "{$imported} " . ($imported === 1 ? 'transação importada' : 'transações importadas') . ' com sucesso.'
            : 'Nenhuma transação foi importada.';

        if ($failed > 0) {
            $message .= " {$failed} " . ($failed === 1 ? 'falhou' : 'falharam') . '.';
        }

        return response()->json([
            'message'  => $message,
            'imported' => $imported,
            'failed'   => $failed,
            'errors'   => $errors,
        ]);
    }
}
