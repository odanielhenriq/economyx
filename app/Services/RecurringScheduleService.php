<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use Carbon\Carbon;

/**
 * Service responsável por calcular datas e períodos de transações recorrentes.
 * 
 * Este service determina:
 * - Se uma transação recorrente se aplica a um mês específico
 * - Qual a data de vencimento para um mês específico
 * - Ajusta dias para meses com menos dias (ex: 31 em fevereiro)
 * 
 * @see App\Console\Commands\RecurringMaterializeCommand
 * @see App\Services\CashflowService
 */
class RecurringScheduleService
{
    /**
     * Verifica se uma transação recorrente se aplica a um mês específico.
     * 
     * Regras:
     * - Template deve estar ativo
     * - Mês deve estar dentro do período (start_date/end_date)
     * - Se yearly, mês deve coincidir com o mês do start_date
     * 
     * @param \App\Models\RecurringTransaction $template Template recorrente
     * @param int $year Ano
     * @param int $month Mês (1-12)
     * @param \Carbon\Carbon|null $now Data atual (para testes)
     * @return bool True se se aplica ao mês
     */
    public function appliesToMonth(RecurringTransaction $template, int $year, int $month, ?Carbon $now = null): bool
    {
        if (! $template->is_active) {
            return false;
        }

        $now = $now ?: Carbon::now();
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        if ($template->start_date && $template->start_date->gt($monthEnd)) {
            return false;
        }

        if ($template->end_date && $template->end_date->lt($monthStart)) {
            return false;
        }

        $frequency = $template->frequency ?: 'monthly';

        if ($frequency === 'yearly') {
            $yearlyBase = $template->start_date ?: $now;

            if ((int) $yearlyBase->month !== (int) $month) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula a data de vencimento de uma transação recorrente para um mês específico.
     * 
     * Usa o day_of_month do template, ou o dia do start_date se não especificado.
     * Ajusta automaticamente para meses com menos dias.
     * 
     * @param \App\Models\RecurringTransaction $template Template recorrente
     * @param int $year Ano
     * @param int $month Mês (1-12)
     * @param \Carbon\Carbon|null $now Data atual (para testes)
     * @return \Carbon\Carbon|null Data de vencimento ou null se não se aplica
     */
    public function dueDateForMonth(RecurringTransaction $template, int $year, int $month, ?Carbon $now = null): ?Carbon
    {
        $now = $now ?: Carbon::now();

        if (! $this->appliesToMonth($template, $year, $month, $now)) {
            return null;
        }

        $startDate = $template->start_date;
        $day = $template->day_of_month ?: ($startDate?->day ?? 1);
        $day = $this->clampDay($year, $month, $day);

        return Carbon::create($year, $month, $day);
    }

    /**
     * Ajusta o dia para o máximo permitido no mês.
     * 
     * Exemplo: Se o template tem day_of_month = 31 e o mês tem 28 dias,
     * retorna 28.
     * 
     * @param int $year Ano
     * @param int $month Mês (1-12)
     * @param int $day Dia desejado
     * @return int Dia ajustado (entre 1 e último dia do mês)
     */
    private function clampDay(int $year, int $month, int $day): int
    {
        $base = Carbon::create($year, $month, 1);

        return min(max($day, 1), $base->daysInMonth);
    }
}
