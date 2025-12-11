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
        $card = CreditCard::find($transaction->credit_card_id);

        $installments = $transaction->installment_total ?: 1;

        // calcula o valor de cada parcela
        if ($installments > 1) {
            $amountPerInstallment = round($transaction->total_amount / $installments, 2);
        } else {
            $amountPerInstallment = $transaction->amount; // à vista
        }

        for ($i = 1; $i <= $installments; $i++) {

            $date = Carbon::parse($transaction->transaction_date)->addMonths($i - 1);

            $year = $date->year;
            $month = $date->month;

            $statement = CreditCardStatement::firstOrCreate(
                [
                    'credit_card_id' => $card->id,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'closing_day'  => $card->closing_day,
                    'due_day'      => $card->due_day,
                    'period_start' => $this->calcPeriodStart($card, $year, $month),
                    'period_end'   => $this->calcPeriodEnd($card, $year, $month),
                ]
            );

            TransactionInstallment::create([
                'transaction_id'           => $transaction->id,
                'credit_card_statement_id' => $statement->id,
                'installment_number'       => $i,
                'installment_total'        => $installments,
                'amount'                   => $amountPerInstallment,
                'year'                     => $year,
                'month'                    => $month,
            ]);
        }
    }

    private function calcPeriodStart($card, $y, $m)
    {
        return Carbon::create($y, $m, $card->closing_day)->subMonth()->addDay();
    }

    private function calcPeriodEnd($card, $y, $m)
    {
        return Carbon::create($y, $m, $card->closing_day);
    }
}
