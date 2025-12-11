<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\CreditCardStatement;
use App\Models\Transaction;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Http\Request;

class CardStatementController extends Controller
{
    public function __construct(
        // Por enquanto esse repository nem está sendo usado nesse controller,
        // mas já está injetado caso você queira reaproveitar depois
        private readonly TransactionRepositoryInterface $transactions,
    ) {}

    public function statement($cardId, Request $request)
    {
        // Lê o ano e o mês da query string (?year=2025&month=12)
        // Se não vier nada, usa ano e mês atuais
        $year  = (int)($request->query('year')  ?? now()->year);
        $month = (int)($request->query('month') ?? now()->month);

        // Busca a fatura (CreditCardStatement) desse cartão/ano/mês
        // Já carrega junto:
        // - o cartão e o dono (card.owner)
        // - as parcelas (installments) com a transaction e categoria
        $statement = CreditCardStatement::with([
            'card.owner',
            'installments.transaction.category',
        ])
            ->forMonth($cardId, $year, $month);

        // Se ainda não existe fatura pra esse mês
        if (! $statement) {
            return response()->json([
                'card' => null,
                'period' => null,
                'summary' => [
                    'income'  => 0,
                    'expense' => 0,
                    'net'     => 0,
                ],
                'transactions' => [],
            ]);
        }

        // 1) Transações PARCELADAS (vêm da tabela transaction_installments)
        $parceladas = $statement->installments->map(function ($inst) {
            // Pega a transação original daquela parcela
            $t = $inst->transaction;

            return [
                'id'          => $t->id,
                'description' => $t->description,
                'amount'      => (float)$inst->amount,

                // Data que será mostrada na fatura:
                // - se tiver due_date na parcela, usa ela
                // - senão, cai pra transaction_date
                'date'        => optional($inst->due_date)->toDateString()
                    ?? optional($t->transaction_date)->toDateString(),

                'installments' => [
                    // true se tem mais de 1 parcela
                    'is_installment' => $inst->installment_total > 1,
                    'number'         => $inst->installment_number,
                    'total'          => $inst->installment_total,
                    // Ex.: "3/10" ou null se não for parcelado
                    'label'          => $inst->installment_total > 1
                        ? "{$inst->installment_number}/{$inst->installment_total}"
                        : null,
                ],

                // Categoria da transação (se tiver)
                'category' => $t->category
                    ? [
                        'id'   => $t->category->id,
                        'name' => $t->category->name,
                    ]
                    : null,
            ];
        });

        // Pega os IDs das transações que já apareceram como parcela
        // pra não duplicar na parte de "à vista"
        $transactionIdsParceladas = $statement->installments
            ->pluck('transaction_id')
            ->unique();

        // 2) Compras À VISTA (sem parcelas) → vêm direto da tabela transactions
        $aVista = Transaction::with('category')
            ->where('credit_card_id', $cardId)
            ->where(function ($q) {
                // installment_total nulo OU <= 1 significa à vista
                $q->whereNull('installment_total')
                    ->orWhere('installment_total', '<=', 1);
            })
            // Garante que você não traga transações que já saíram nas parcelas
            ->whereNotIn('id', $transactionIdsParceladas)
            // Só dentro do período daquela fatura (period_start -> period_end)
            ->whereBetween('transaction_date', [
                $statement->period_start,
                $statement->period_end,
            ])
            ->get()
            ->map(function ($t) {
                return [
                    'id'          => $t->id,
                    'description' => $t->description,
                    'amount'      => (float)$t->amount,
                    'date'        => optional($t->transaction_date)->toDateString(),

                    // Como é à vista, não tem info de parcelas
                    'installments' => [
                        'is_installment' => false,
                        'number'         => null,
                        'total'          => null,
                        'label'          => null,
                    ],

                    'category' => $t->category
                        ? [
                            'id'   => $t->category->id,
                            'name' => $t->category->name,
                        ]
                        : null,
                ];
            });

        // 3) Junta PARCELADAS + À VISTA e ordena por data
        $transactions = $parceladas
            ->concat($aVista)
            ->sortBy('date')
            ->values(); // reseta os índices

        // 4) Calcula o resumo (aqui você tratou tudo como despesa)
        $total = $transactions->sum('amount');

        $summary = [
            'income'  => 0,          // não está diferenciando receita/despesa aqui
            'expense' => $total,     // tudo é gasto
            'net'     => -$total,    // saldo negativo
        ];

        // Resposta final da API de fatura
        return response()->json([
            'card' => [
                'id'          => $statement->card->id,
                'name'        => $statement->card->name,
                'owner'       => optional($statement->card->owner)->name,
                'closing_day' => $statement->closing_day,
                'due_day'     => $statement->due_day,
            ],
            'period' => [
                'start' => $statement->period_start->toDateString(),
                'end'   => $statement->period_end->toDateString(),
            ],
            'summary'      => $summary,
            'transactions' => $transactions,
        ]);
    }
}
