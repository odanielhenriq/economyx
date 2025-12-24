<?php

namespace App\Services;

use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\TransactionInstallment;
use Carbon\Carbon;

class GenerateInstallmentsService
{
    public function generate($transaction)
    {
        // Decide qual fluxo usar baseado no que a transação é

        // 1) Se tem cartão → gera parcelas ligadas a faturas de cartão
        if ($transaction->credit_card_id) {
            return $this->generateForCreditCard($transaction);
        }

        // 2) Se NÃO tem cartão mas está parcelada E é "tipo empréstimo"
        //    → gera parcelas como debito em conta/boletos (sem fatura de cartão)
        if ($transaction->installment_total > 1 && $this->isLoanLike($transaction)) {
            return $this->generateForLoan($transaction);
        }

        // 3) Caso contrário, não gera nada (ex.: compra à vista sem cartão)
    }

    /**
     * Gera parcelas para compras de cartão de crédito
     * (entra na tabela credit_card_statements + transaction_installments).
     */
    private function generateForCreditCard($transaction): void
    {
        // Carrega o cartão da transação
        $card = CreditCard::find($transaction->credit_card_id);

        // Total de parcelas (se vier null, trata como 1)
        $installments = $transaction->installment_total ?: 1;

        // Se não é parcelado (1x ou null), não gera registros na tabela de installments
        // Compra à vista aparece direto em `transactions` e será tratada no extrato
        if ($installments < 2) {
            return;
        }

        // Aqui você está assumindo que `amount` já é o valor de CADA parcela.
        // `total_amount` representa o total da compra.
        $amountPerInstallment = $transaction->amount;

        $purchaseDate = Carbon::parse($transaction->transaction_date);

        // começa testando o mês seguinte ao da compra como vencimento provável
        $cursor = $purchaseDate->copy()->addMonthNoOverflow()->startOfMonth();

        // acha a primeira fatura cujo fechamento (period_end) ainda não passou da compra
        while ($purchaseDate->gt($this->calcPeriodEnd($card, $cursor->year, $cursor->month))) {
            $cursor->addMonthNoOverflow();
        }

        $baseYear  = $cursor->year;
        $baseMonth = $cursor->month;


        // Loop de 1 até o número total de parcelas
        for ($i = 1; $i <= $installments; $i++) {
            // Calcula em qual mês essa parcela cai
            // Ex.: compra em agosto, i=1 → ago, i=2 → set, etc
            $statementMonth = Carbon::create($baseYear, $baseMonth, 1)->addMonthsNoOverflow($i - 1);
            $year  = $statementMonth->year;
            $month = $statementMonth->month;

            // Busca ou cria a fatura (CreditCardStatement) daquele mês
            $statement = CreditCardStatement::firstOrCreate(
                [
                    'credit_card_id' => $card->id,
                    'year'           => $year,
                    'month'          => $month,
                ],
                [
                    'closing_day'  => $card->closing_day,
                    'due_day'      => $card->due_day,
                    'period_start' => $this->calcPeriodStart($card, $year, $month),
                    'period_end'   => $this->calcPeriodEnd($card, $year, $month),
                ]
            );

            // Cria o registro da parcela na tabela `transaction_installments`
            $installmentDueDate = $this->calcDueDate($card, $year, $month);
            
            TransactionInstallment::create([
                'transaction_id'           => $transaction->id,
                'credit_card_statement_id' => $statement->id,
                'installment_number'       => $i,
                'installment_total'        => $installments,
                'amount'                   => $amountPerInstallment,
                'year'                     => $year,
                'month'                    => $month,
                'due_date'                 => $installmentDueDate,
            ]);
            
            // Atualiza o due_date da transação principal com o due_date da primeira parcela
            if ($i === 1 && !$transaction->due_date) {
                $transaction->due_date = $installmentDueDate;
                $transaction->save();
            }
        }
    }


    /**
     * Gera parcelas para empréstimos/financiamentos (sem cartão de crédito).
     * Tudo cai em `transaction_installments` com `credit_card_statement_id = null`.
     */
    private function generateForLoan($transaction): void
    {
        $installments = $transaction->installment_total ?: 1;
        $amountPerInstallment = $transaction->amount;
        
        // Se a transação já tem installment_number, significa que já estava em andamento
        // Nesse caso, as parcelas geradas devem começar desse número
        $startInstallmentNumber = $transaction->installment_number ?: 1;
        $remainingInstallments = $installments - $startInstallmentNumber + 1;

        // base real do vencimento
        $base = $transaction->first_due_date
            ? Carbon::parse($transaction->first_due_date)
            : Carbon::parse($transaction->transaction_date)->addMonthNoOverflow();

        // Se já estava em andamento, ajusta a base para a parcela atual
        if ($startInstallmentNumber > 1) {
            $base = $base->copy()->addMonthsNoOverflow($startInstallmentNumber - 1);
        }

        for ($i = 0; $i < $remainingInstallments; $i++) {
            $installmentNumber = $startInstallmentNumber + $i;
            $dueDate = $base->copy()->addMonthsNoOverflow($i);

            TransactionInstallment::create([
                'transaction_id'           => $transaction->id,
                'credit_card_statement_id' => null,
                'installment_number'       => $installmentNumber,
                'installment_total'        => $installments,
                'amount'                   => $amountPerInstallment,
                'year'                     => $dueDate->year,
                'month'                    => $dueDate->month,
                'due_date'                 => $dueDate,
            ]);
            
            // Atualiza o due_date da transação principal com o due_date da primeira parcela gerada
            if ($i === 0 && !$transaction->due_date) {
                $transaction->due_date = $dueDate;
                $transaction->save();
            }
        }
    }



    /**
     * Decide se uma transação "parece" empréstimo/financiamento.
     * Aqui a regra é:
     *  - categoria_id == 10 (Empréstimos)
     *  - ou payment_method_id == 5 (Débito em conta)
     */
    private function isLoanLike($transaction): bool
    {
        $categorySlug = $transaction->category?->slug;
        $paymentMethodSlug = $transaction->paymentMethod?->slug;

        return in_array($categorySlug, ['ep'], true) // 'ep' = Empréstimos
            || $paymentMethodSlug === 'tb'; // 'tb' = Transferência Bancária
    }


    // Calcula o vencimento no mês da fatura
    private function calcDueDate($card, $y, $m)
    {
        $d = Carbon::create($y, $m, 1);
        return $d->copy()->day(min($card->due_day, $d->daysInMonth));
    }

    private function calcPeriodEnd($card, $y, $m)
    {
        [, $end] = $card->getStatementPeriodForDueMonth($y, $m);
        return $end;
    }

    private function calcPeriodStart($card, $y, $m)
    {
        [$start, ] = $card->getStatementPeriodForDueMonth($y, $m);
        return $start;
    }
}
