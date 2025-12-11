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

        // Loop de 1 até o número total de parcelas
        for ($i = 1; $i <= $installments; $i++) {
            // Calcula em qual mês essa parcela cai
            // Ex.: compra em agosto, i=1 → ago, i=2 → set, etc
            $date = Carbon::parse($transaction->transaction_date)->addMonths($i - 1);

            $year  = $date->year;
            $month = $date->month;

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
            TransactionInstallment::create([
                'transaction_id'           => $transaction->id,
                'credit_card_statement_id' => $statement->id,
                'installment_number'       => $i,
                'installment_total'        => $installments,
                'amount'                   => $amountPerInstallment,
                'year'                     => $year,
                'month'                    => $month,
                'due_date'                 => $statement->period_end, // vence no fechamento
            ]);
        }
    }


    /**
     * Gera parcelas para empréstimos/financiamentos (sem cartão de crédito).
     * Tudo cai em `transaction_installments` com `credit_card_statement_id = null`.
     */
    private function generateForLoan($transaction): void
    {
        $installments = $transaction->installment_total ?: 1;

        // Aqui você decidiu que `amount` já é o valor da parcela.
        $amountPerInstallment = $transaction->amount;

        for ($i = 1; $i <= $installments; $i++) {

            // Cada mês é uma parcela a vencer
            $dueDate = Carbon::parse($transaction->transaction_date)->addMonths($i - 1);

            TransactionInstallment::create([
                'transaction_id'           => $transaction->id,
                'credit_card_statement_id' => null, // não está ligado a cartão
                'installment_number'       => $i,
                'installment_total'        => $installments,
                'amount'                   => $amountPerInstallment,
                'year'                     => $dueDate->year,
                'month'                    => $dueDate->month,
                'due_date'                 => $dueDate,
            ]);
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
        return in_array($transaction->category_id, [10]) // 10 = empréstimo
            || $transaction->payment_method_id == 5;
    }


    // Calcula o início do período da fatura (dia depois do fechamento anterior)
    private function calcPeriodStart($card, $y, $m)
    {
        return Carbon::create($y, $m, $card->closing_day)->subMonth()->addDay();
    }

    // Calcula o fim do período (dia do fechamento)
    private function calcPeriodEnd($card, $y, $m)
    {
        return Carbon::create($y, $m, $card->closing_day);
    }
}
