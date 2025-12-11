<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\CreditCardStatement;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Http\Request;

class CardStatementController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {}

    public function show(Request $request, int $cardId)
{
    $year  = (int) ($request->query('year')  ?? now()->year);
    $month = (int) ($request->query('month') ?? now()->month);

    $statement = CreditCardStatement::with([
            'card.owner',
            'installments.transaction.category',
            'installments.transaction.type',
        ])
        ->forMonth($cardId, $year, $month);

    if (!$statement) {
        // não tem fatura gerada pra esse mês
        $card = \App\Models\CreditCard::with('owner')->findOrFail($cardId);

        return response()->json([
            'card' => [
                'id'    => $card->id,
                'name'  => $card->name,
                'owner' => $card->owner->name ?? null,
                'closing_day' => $card->closing_day,
                'due_day'     => $card->due_day,
            ],
            'period' => [
                'start' => null,
                'end'   => null,
            ],
            'summary' => [
                'income'  => 0,
                'expense' => 0,
                'net'     => 0,
            ],
            'transactions' => [],
        ]);
    }

    $installments = $statement->installments;

    $income  = 0;
    $expense = 0;

    $transactions = $installments->map(function ($inst) use (&$income, &$expense) {

        $tx   = $inst->transaction;
        $type = $tx->type->name ?? null;

        // AJUSTA essa regra conforme teu domínio:
        $isIncome = $type === 'Receita'; // ou type_id == 1, etc.

        $value = (float) $inst->amount;

        if ($isIncome) {
            $income += $value;
        } else {
            $expense -= $value; // negativo
        }

        return [
            'id'           => $tx->id,
            'description'  => $tx->description,
            'total_amount' => (float) $tx->total_amount,
            'amount'       => number_format($inst->amount, 2, '.', ''),
            'signed_amount'=> $isIncome ? $value : -$value,
            'date'         => $tx->transaction_date->toDateString(),

            'installments' => [
                'number'        => $inst->installment_number,
                'total'         => $inst->installment_total,
                'is_installment'=> $inst->installment_total > 1,
                'label'         => $inst->installment_total > 1
                    ? "{$inst->installment_number}/{$inst->installment_total}"
                    : null,
                'remaining'     => $inst->installment_total - $inst->installment_number,
            ],

            'category' => [
                'name'  => $tx->category->name ?? null,
                'color' => null,
            ],
            'type' => [
                'name'  => $type,
                'color' => null,
            ],
            'payment_method' => [
                'name' => 'Credit Card', // ou $tx->paymentMethod->name
            ],
            'credit_card' => [
                'id'          => $tx->credit_card_id,
                'name'        => $tx->creditCard->name ?? null,
                'owner_label' => $tx->creditCard->owner->name ?? null,
            ],
            // se quiser users depois, você puxa a relação e monta aqui
            'users' => [],
            'totals' => [
                'total_amount'   => (float) $tx->total_amount,
                'per_user_share' => (float) $inst->amount, // pode ajustar por usuário depois
            ],
        ];
    });

    $summary = [
        'income'  => $income,
        'expense' => $expense,
        'net'     => $income + $expense,
    ];

    return response()->json([
        'card' => [
            'id'    => $statement->card->id,
            'name'  => $statement->card->name,
            'owner' => $statement->card->owner->name ?? null,
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


    public function statement($cardId, Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        $statement = CreditCardStatement::with('installments.transaction.category')
            ->forMonth($cardId, $year, $month);

        if (!$statement) {
            return response()->json(['transactions' => []]);
        }

        $transactions = $statement->installments->map(function ($inst) {
            return [
                'description' => $inst->transaction->description,
                'amount' => $inst->amount,
                'date' => $inst->transaction->transaction_date,
                'installment' => "{$inst->installment_number}/{$inst->installment_total}",
                'category' => $inst->transaction->category->name ?? null,
            ];
        });

        return response()->json([
            'statement' => $statement,
            'transactions' => $transactions,
        ]);
    }
}
