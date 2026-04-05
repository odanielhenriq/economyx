<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Services\RecurringScheduleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Comando Artisan para materializar transações recorrentes.
 * 
 * Este comando cria transações reais na tabela `transactions` baseado
 * nos templates de transações recorrentes ativas.
 * 
 * Funcionamento:
 * 1. Busca todos os templates recorrentes ativos
 * 2. Para cada template, verifica se se aplica ao mês especificado
 * 3. Verifica se já existe transação materializada para aquele mês
 * 4. Se não existe, cria uma nova transação real
 * 5. Vincula aos mesmos usuários do template
 * 
 * Agendamento:
 * - Roda automaticamente todo dia às 02:00
 * - Pode ser executado manualmente: php artisan recurring:materialize
 * 
 * @see routes/console.php Para agendamento
 * @see App\Models\RecurringTransaction Para templates
 */
class RecurringMaterializeCommand extends Command
{
    protected $signature = 'recurring:materialize {--year=} {--month=}';

    protected $description = 'Materializa transações recorrentes para o mês alvo';

    /**
     * Executa o comando de materialização.
     * 
     * @param \App\Services\RecurringScheduleService $schedule Service para calcular datas
     * @return int Código de saída (0 = sucesso)
     */
    public function handle(RecurringScheduleService $schedule): int
    {
        $now = Carbon::now();
        $year = (int) ($this->option('year') ?: $now->year);
        $month = (int) ($this->option('month') ?: $now->month);

        if ($month < 1 || $month > 12) {
            $this->error('Mês inválido. Use 1-12.');
            return self::FAILURE;
        }

        $templates = RecurringTransaction::with('users')
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($templates as $template) {
            $dueDate = $schedule->dueDateForMonth($template, $year, $month, $now);

            if (! $dueDate) {
                continue;
            }

            if ($template->credit_card_id && ($year !== $now->year || $month !== $now->month)) {
                continue;
            }

            $exists = Transaction::query()
                ->where('recurring_transaction_id', $template->id)
                ->whereDate('due_date', $dueDate->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            $transaction = Transaction::create([
                'description' => $template->description,
                'amount' => $template->amount,
                'total_amount' => $template->total_amount ?? $template->amount,
                'category_id' => $template->category_id,
                'type_id' => $template->type_id,
                'payment_method_id' => $template->payment_method_id,
                'credit_card_id' => $template->credit_card_id,
                'transaction_date' => $dueDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'recurring_transaction_id' => $template->id,
            ]);

            $transaction->users()->sync($template->users->pluck('id')->all());
            $created++;
        }

        $this->info("Transações materializadas: {$created}");

        return self::SUCCESS;
    }
}
