<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\Transaction;
use Illuminate\Http\Request;

class CardStatementController extends Controller
{
    public function statement($cardId, Request $request)
    {
        $year  = (int)($request->query('year')  ?? now()->year);
        $month = (int)($request->query('month') ?? now()->month);

        // 1) Busca o cartão direto (SEM user)
        $card = CreditCard::with('owner')->findOrFail($cardId);

        if (!$card->closing_day) {
            return response()->json(['message' => 'Cartão sem closing_day configurado.'], 422);
        }

        // 2) Tenta buscar statement (se existir, ótimo — traz parcelas)
        $statement = CreditCardStatement::with([
            'installments.transaction.category',
        ])->where('credit_card_id', $cardId)
          ->where('year', $year)
          ->where('month', $month)
          ->first();

        // Mantém o status sempre atualizado quando a fatura é carregada
        if ($statement) {
            $statement->updateStatus();
        }

        $installments = $statement?->installments ?? collect();

        // 3) Período baseado no mês de vencimento (regra padrão do app)
        [$periodStart, $periodEnd] = $card->getStatementPeriodForDueMonth($year, $month);

        // 4) Parceladas
        $parceladas = $installments->map(function ($inst) {
            $t = $inst->transaction;

            return [
                'id'          => $t->id,
                'description' => $t->description,
                'amount'      => (float)$inst->amount,
                'date'        => optional($inst->due_date)->toDateString()
                    ?? optional($t->transaction_date)->toDateString(),
                'installments' => [
                    'is_installment' => (int)$inst->installment_total > 1,
                    'number'         => (int)$inst->installment_number,
                    'total'          => (int)$inst->installment_total,
                    'label'          => (int)$inst->installment_total > 1
                        ? "{$inst->installment_number}/{$inst->installment_total}"
                        : null,
                ],
                'category' => $t->category
                    ? ['id' => $t->category->id, 'name' => $t->category->name]
                    : null,
            ];
        });

        $transactionIdsParceladas = $installments->pluck('transaction_id')->unique();

        // 5) À vista (1x) — SEM precisar statement existir
        $aVistaQuery = Transaction::with('category')
            ->where('credit_card_id', $cardId)
            ->where(function ($q) {
                $q->whereNull('installment_total')
                  ->orWhere('installment_total', '<=', 1);
            })
            ->whereBetween('transaction_date', [$periodStart, $periodEnd]);

        if ($transactionIdsParceladas->isNotEmpty()) {
            $aVistaQuery->whereNotIn('id', $transactionIdsParceladas);
        }

        $aVista = $aVistaQuery->get()->map(function ($t) {
            return [
                'id'          => $t->id,
                'description' => $t->description,
                'amount'      => (float)$t->amount,
                'date'        => optional($t->transaction_date)->toDateString(),
                'installments' => [
                    'is_installment' => false,
                    'number'         => null,
                    'total'          => null,
                    'label'          => null,
                ],
                'category' => $t->category
                    ? ['id' => $t->category->id, 'name' => $t->category->name]
                    : null,
            ];
        });

        $transactions = $parceladas->concat($aVista)->sortBy('date')->values();
        $total = $transactions->sum('amount');

        return response()->json([
            'card' => [
                'id'          => $card->id,
                'name'        => $card->name,
                'owner'       => optional($card->owner)->name,
                'closing_day' => (int)$card->closing_day,
                'due_day'     => (int)$card->due_day,
            ],
            'period' => [
                'start' => $periodStart->toDateString(),
                'end'   => $periodEnd->toDateString(),
            ],
            'summary' => [
                'income'  => 0,
                'expense' => $total,
                'net'     => -$total,
            ],
            'transactions' => $transactions,
            'meta' => [
                'statement_persisted' => (bool)$statement,
                'statement_id'        => $statement?->id,
                'status'              => $statement?->status ?? 'open',
            ],
        ]);
    }

    /**
     * Marca uma fatura como paga.
     * Só usuários da rede do dono do cartão podem executar essa ação.
     */
    public function markAsPaid(CreditCardStatement $statement)
    {
        $card = $statement->creditCard;
        $user = auth()->user();

        $inNetwork = $card->users()->where('users.id', $user->id)->exists()
            || $card->owner_user_id === $user->id;

        abort_if(! $inNetwork, 403);

        $statement->update(['status' => 'paid']);

        return response()->json(['status' => 'paid']);
    }

}
