<?php

namespace App\Services;

use App\Models\CreditCardStatement;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\User;
use Carbon\Carbon;

/**
 * Service responsável por construir os dados do dashboard mensal.
 * 
 * Este service agrega todas as informações necessárias para exibir o dashboard:
 * - Totais de receitas e despesas do mês
 * - Faturas de cartões a pagar
 * - Parcelas de empréstimos a pagar
 * - Itens do fluxo de caixa
 * 
 * Fluxo:
 * 1. Calcula receitas/despesas à vista (excluindo parceladas)
 * 2. Adiciona projeções de transações recorrentes
 * 3. Busca faturas de cartões do mês
 * 4. Busca parcelas de empréstimos do mês
 * 5. Monta array com todos os dados
 * 
 * @see App\Http\Controllers\Api\MonthlyDashboardController
 */
class MonthlyDashboardService
{
    /**
     * Constrói todos os dados do dashboard para um mês específico.
     * 
     * @param int $year Ano
     * @param int $month Mês (1-12)
     * @param \App\Models\User $user Usuário logado
     * @return array Array com cards (totais) e lists (itens detalhados)
     */
    public function build(int $year, int $month, User $user): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $networkIds = $user->networkUsers()->pluck('id')->all();
        $installmentParentIds = TransactionInstallment::query()
            ->select('transaction_id')
            ->distinct()
            ->pluck('transaction_id')
            ->all();

        // Transações à vista (excluindo parceladas)
        $transactionBase = Transaction::query()
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            });

        if (! empty($installmentParentIds)) {
            $transactionBase->whereNotIn('id', $installmentParentIds);
        }

        // Receitas do mês (transações à vista)
        $incomeTotal = (clone $transactionBase)
            ->whereHas('type', function ($query) {
                $query->where('slug', 'rc');
            })
            ->sum('amount');

        // Despesas diretas do mês (transações à vista, sem cartão, sem empréstimos)
        $directExpenseTotal = (clone $transactionBase)
            ->whereNull('credit_card_id')
            ->whereHas('type', function ($query) {
                $query->where('slug', 'dc');
            })
            ->where(function ($query) {
                $query->whereDoesntHave('category', function ($subQuery) {
                    $subQuery->where('slug', 'ep');
                })->whereDoesntHave('paymentMethod', function ($subQuery) {
                    $subQuery->where('slug', 'tb');
                });
            })
            ->sum('amount');

        // NOTA: Parcelas de cartão NÃO entram nas despesas/receitas do mês
        // Elas aparecem apenas em "A Pagar - Cartões" para evitar duplicação
        // As parcelas aparecem no fluxo de caixa apenas para visualização

        // Adiciona projeções de transações recorrentes aos totais
        $schedule = new RecurringScheduleService();
        $now = Carbon::now();
        $recurringTemplates = \App\Models\RecurringTransaction::query()
            ->with('type')
            ->where('is_active', true)
            ->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->get();

        $projectedIncome = 0.0;
        $projectedExpense = 0.0;

        foreach ($recurringTemplates as $template) {
            $dueDate = $schedule->dueDateForMonth($template, $year, $month, $now);

            if (!$dueDate) {
                continue;
            }

            // Verifica se já existe transação materializada para este mês
            $exists = Transaction::query()
                ->where('recurring_transaction_id', $template->id)
                ->whereDate('due_date', $dueDate->toDateString())
                ->exists();

            if ($exists) {
                continue; // Já está materializada, não precisa projetar
            }

            // Adiciona aos totais projetados
            if ($template->type?->slug === 'rc') {
                $projectedIncome += (float) $template->amount;
            } elseif ($template->type?->slug === 'dc') {
                $projectedExpense += (float) $template->amount;
            }
        }

        // Adiciona projeções aos totais
        $incomeTotal += $projectedIncome;
        $directExpenseTotal += $projectedExpense;

        $cards = $user->creditCards()->with('owner')->get();
        $statements = CreditCardStatement::with(['installments'])
            ->whereIn('credit_card_id', $cards->pluck('id')->all())
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('credit_card_id');

        $payablesCards = [];
        $payablesCardsTotal = 0.0;

        foreach ($cards as $card) {
            $statement = $statements->get($card->id);
            $installments = $statement?->installments ?? collect();
            $installmentsTotal = (float) $installments->sum('amount');
            $transactionIdsParceladas = $installments->pluck('transaction_id')->unique();

            [$periodStart, $periodEnd] = $card->getStatementPeriodForDueMonth($year, $month);
            $dueDay = $statement?->due_day ?: $card->due_day;

            $aVistaQuery = Transaction::query()
                ->where('credit_card_id', $card->id)
                ->where(function ($query) {
                    $query->whereNull('installment_total')
                        ->orWhere('installment_total', '<=', 1);
                })
                ->whereBetween('transaction_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ]);

            if ($transactionIdsParceladas->isNotEmpty()) {
                $aVistaQuery->whereNotIn('id', $transactionIdsParceladas);
            }

            $aVistaTotal = (float) $aVistaQuery->sum('amount');
            $total = $installmentsTotal + $aVistaTotal;

            if (! $statement && $total <= 0) {
                continue;
            }

            $dueDate = $this->statementDueDate(
                $year,
                $month,
                $dueDay
            );

            $payablesCardsTotal += $total;

            $payablesCards[] = [
                'card_name' => $card->name,
                'owner_name' => $card->owner?->name,
                'due_date' => $dueDate->toDateString(),
                'total' => $total,
            ];
        }

        $loanInstallments = TransactionInstallment::with('transaction')
            ->whereNull('credit_card_statement_id')
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('transaction.users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->get();

        $payablesLoans = $loanInstallments->map(function (TransactionInstallment $installment) {
            $transaction = $installment->transaction;

            return [
                'transaction_id' => $installment->transaction_id,
                'description' => $transaction?->description,
                'due_date' => $installment->due_date?->toDateString(),
                'amount' => (float) $installment->amount,
                'installment_number' => $installment->installment_number,
                'installment_total' => $installment->installment_total,
            ];
        })->all();

        $payablesLoansTotal = (float) $loanInstallments->sum('amount');

        $loanTransactionIds = $loanInstallments->pluck('transaction_id')->unique()->all();
        $loanFallbackTransactions = Transaction::with(['category', 'paymentMethod'])
            ->whereNull('credit_card_id')
            ->whereHas('users', function ($query) use ($networkIds) {
                $query->whereIn('users.id', $networkIds);
            })
            ->whereNotIn('id', $loanTransactionIds)
            ->where(function ($query) {
                $query->whereHas('category', function ($subQuery) {
                    $subQuery->where('slug', 'ep');
                })->orWhereHas('paymentMethod', function ($subQuery) {
                    $subQuery->where('slug', 'tb');
                });
            })
            ->get();

        foreach ($loanFallbackTransactions as $transaction) {
            $fallback = $this->loanFallbackForMonth($transaction, $year, $month);

            if (! $fallback) {
                continue;
            }

            $payablesLoans[] = $fallback;
            $payablesLoansTotal += (float) $fallback['amount'];
        }

        usort($payablesLoans, function (array $a, array $b) {
            return strcmp($a['due_date'] ?? '', $b['due_date'] ?? '');
        });
        $expenseTotal = (float) $directExpenseTotal + $payablesCardsTotal + $payablesLoansTotal;
        $balanceTotal = (float) $incomeTotal - $expenseTotal;
        $payableTotal = $payablesCardsTotal + $payablesLoansTotal;

        $cashflowItems = app(CashflowService::class)
            ->forMonth($year, $month, true, $user);

        return [
            'cards' => [
                'income_total_month' => (float) $incomeTotal,
                'expense_total_month' => (float) $expenseTotal,
                'balance_month' => (float) $balanceTotal,
                'payable_total_month' => (float) $payableTotal,
                'breakdown' => [
                    'payable_cards_total' => (float) $payablesCardsTotal,
                    'payable_loans_total' => (float) $payablesLoansTotal,
                ],
            ],
            'lists' => [
                'payables_cards' => $payablesCards,
                'payables_loans' => $payablesLoans,
                'cashflow_items' => $cashflowItems,
            ],
        ];
    }

    private function statementDueDate(int $year, int $month, ?int $dueDay): Carbon
    {
        $day = $dueDay ?: 1;
        $base = Carbon::create($year, $month, 1)->startOfDay();
        $day = min(max($day, 1), $base->daysInMonth);

        return $base->copy()->day($day);
    }

    private function loanFallbackForMonth(Transaction $transaction, int $year, int $month): ?array
    {
        $totalInstallments = (int) ($transaction->installment_total ?: 1);
        $startInstallmentNumber = (int) ($transaction->installment_number ?: 1);

        // Calcula a data base: se tem first_due_date, usa ela; senão, usa transaction_date + 1 mês
        $base = $transaction->first_due_date
            ? Carbon::parse($transaction->first_due_date)
            : Carbon::parse($transaction->transaction_date)->addMonthNoOverflow();

        // Se a transação já está em andamento (installment_number > 1),
        // a base atual representa quando a parcela 1 seria se começasse do zero.
        // Mas como estamos na parcela startInstallmentNumber, precisamos calcular
        // quando a parcela 1 REAL começou.
        // 
        // Exemplo: transaction_date = ago/2025, base = set/2025 (parcela 1 se começasse do zero)
        // Mas installment_number = 11 significa que já pagamos 10 parcelas.
        // Se a parcela 11 deve aparecer em fev/2026, então:
        // - Parcela 11: fev/2026
        // - Parcela 1: fev/2026 - 10 meses = abr/2025
        // 
        // Então a base real (parcela 1) é: base atual - (startInstallmentNumber - 1) meses
        
        $targetDate = Carbon::create($year, $month, 1);
        
        // Se temos installment_number > 1, calcula quando a parcela 1 REAL começou
        // a partir do mês alvo, retrocedendo (startInstallmentNumber - 1) meses
        if ($startInstallmentNumber > 1) {
            // Exemplo: se installment_number = 11 e queremos fev/2026,
            // a parcela 1 foi em (fev/2026 - 10 meses) = abr/2025
            $parcela1Date = $targetDate->copy()->subMonthsNoOverflow($startInstallmentNumber - 1);
            // Preserva o dia da base original
            $parcela1Date->day(min($base->day, $parcela1Date->daysInMonth));
            $base = $parcela1Date;
        }
        
        $monthDiff = ($targetDate->year - $base->year) * 12 + ($targetDate->month - $base->month);
        
        // O número da parcela é: quantos meses desde a base + 1 (porque base é parcela 1)
        $installmentNumber = $monthDiff + 1;

        // Verifica se a parcela está dentro do range válido
        // A parcela deve estar entre startInstallmentNumber e totalInstallments
        if ($installmentNumber < $startInstallmentNumber || $installmentNumber > $totalInstallments) {
            return null;
        }

        // A data de vencimento é o mesmo dia do mês alvo (preservando o dia da base)
        $dueDate = $targetDate->copy()->day(min($base->day, $targetDate->daysInMonth));

        return [
            'transaction_id' => $transaction->id,
            'description' => $transaction->description,
            'due_date' => $dueDate->toDateString(),
            'amount' => (float) $transaction->amount,
            'installment_number' => $installmentNumber,
            'installment_total' => $totalInstallments,
        ];
    }
}
