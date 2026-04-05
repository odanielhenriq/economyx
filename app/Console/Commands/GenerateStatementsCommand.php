<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\GenerateInstallmentsService;
use Illuminate\Console\Command;

/**
 * Comando para gerar faturas e parcelas de transações existentes.
 * 
 * Este comando é útil quando você criou transações "do zero" (via seeder,
 * importação, etc) e os eventos não foram disparados.
 * 
 * Uso:
 * php artisan statements:generate
 * 
 * O comando:
 * 1. Busca transações com cartão de crédito que são parceladas
 * 2. Verifica quais ainda não têm parcelas geradas
 * 3. Gera as parcelas e faturas automaticamente
 */
class GenerateStatementsCommand extends Command
{
    protected $signature = 'statements:generate';

    protected $description = 'Gera faturas e parcelas para transações existentes que ainda não foram processadas';

    public function handle(GenerateInstallmentsService $service): int
    {
        $this->info('Buscando transações que precisam de parcelas/faturas...');

        // Busca transações com cartão de crédito que são parceladas
        // e que ainda não têm parcelas geradas
        $transactions = Transaction::query()
            ->whereNotNull('credit_card_id')
            ->where(function ($query) {
                $query->where('installment_total', '>', 1)
                      ->orWhereNotNull('installment_total');
            })
            ->whereDoesntHave('installments')
            ->with(['creditCard', 'category', 'paymentMethod'])
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('✅ Nenhuma transação precisa ser processada.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$transactions->count()} transação(ões) para processar.");

        $processed = 0;
        $errors = 0;

        foreach ($transactions as $transaction) {
            try {
                $this->line("Processando: {$transaction->description} (ID: {$transaction->id})");

                // Chama o service para gerar parcelas e faturas
                $service->generate($transaction);

                $processed++;
            } catch (\Throwable $th) {
                $this->error("Erro ao processar transação ID {$transaction->id}: {$th->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("✅ Processadas: {$processed}");
        
        if ($errors > 0) {
            $this->warn("⚠️  Erros: {$errors}");
        }

        return self::SUCCESS;
    }
}


