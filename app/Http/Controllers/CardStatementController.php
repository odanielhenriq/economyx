<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\Transaction;
use Carbon\Carbon;
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

        // 2) Período SEM depender do statement existir
        [$periodStart, $periodEnd] = $this->getBillingPeriodFor($card, $year, $month);

        // 3) Tenta buscar statement (se existir, ótimo — traz parcelas)
        $statement = CreditCardStatement::with([
            'installments.transaction.category',
        ])->where('credit_card_id', $cardId)
          ->where('year', $year)
          ->where('month', $month)
          ->first();

        $installments = $statement?->installments ?? collect();

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
            ],
        ]);
    }

    private function getBillingPeriodFor(CreditCard $card, int $year, int $month): array
    {
        // versão segura (tratando dia 31 em mês menor)
        $closingDay = (int) $card->closing_day;

        $closingDate = $this->safeDate($year, $month, $closingDay)->endOfDay();
        $previousClosingDate = $closingDate->copy()->subMonthNoOverflow()->endOfDay();

        $start = $previousClosingDate->copy()->addDay()->startOfDay();
        $end   = $closingDate->copy();

        return [$start, $end];
    }

    private function safeDate(int $year, int $month, int $day): Carbon
    {
        $base = Carbon::create($year, $month, 1)->startOfDay();
        $day  = max(1, min($day, $base->daysInMonth));
        return $base->copy()->day($day);
    }
}
