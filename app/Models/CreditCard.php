<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Model que representa um cartão de crédito.
 * 
 * Um cartão pode ser:
 * - Individual (de um usuário) ou compartilhado
 * - Ter múltiplos usuários vinculados (N:N)
 * - Ter múltiplas faturas (CreditCardStatement)
 * 
 * Campos Importantes:
 * - closing_day: Dia de fechamento da fatura
 * - due_day: Dia de vencimento da fatura
 * - owner_user_id: ID do dono do cartão
 * - is_shared: Se é compartilhado entre usuários
 * 
 * Relacionamentos:
 * - owner: Dono do cartão (User)
 * - users: Usuários que têm acesso (N:N)
 * - transactions: Transações feitas no cartão
 * - statements: Faturas do cartão
 */
class CreditCard extends Model
{
    protected $fillable = [
        'name',
        'alias',
        'closing_day',
        'due_day',
        'limit',
        'owner_user_id',
        'owner_name',
        'is_shared',
    ];

    // Um cartão tem muitas transações
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Dono do cartão (User)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    // Muitos usuários podem usar esse cartão (tabela pivot credit_card_user)
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'credit_card_user',
            'credit_card_id',
            'user_id'
        );
    }

    /**
     * Retorna o período de faturamento de um ano/mês.
     * 
     * O período de faturamento define quais compras entram em uma fatura:
     * - start = dia seguinte ao fechamento anterior
     * - end   = dia do fechamento atual
     *
     * Exemplo: fechamento dia 5
     * - billing 12/2025 → 06/11/2025 a 05/12/2025
     * - Compras entre essas datas aparecem na fatura de dezembro
     * 
     * @param int $year Ano
     * @param int $month Mês (1-12)
     * @return array [Carbon $start, Carbon $end] Período de faturamento
     */
    public function getBillingPeriodFor(int $year, int $month): array
    {
        $closingDay = (int) $this->closing_day;

        $base = Carbon::create($year, $month, 1)->startOfDay();
        $closingDay = max(1, min($closingDay, $base->daysInMonth));

        $closingDate = $base->copy()->day($closingDay)->endOfDay();
        $previousClosingDate = $closingDate->copy()->subMonthNoOverflow()->endOfDay();

        $start = $previousClosingDate->copy()->addDay()->startOfDay();
        $end   = $closingDate->copy();

        return [$start, $end];
    }

    /**
     * Calcula o período de fechamento baseado no mês de vencimento da fatura.
     * 
     * Lógica:
     * - Se closing_day <= due_day: fechamento no mesmo mês do vencimento
     * - Se closing_day > due_day: fechamento no mês anterior
     * 
     * Exemplo: closing_day = 10, due_day = 15
     * - Fatura vence em dezembro/2025
     * - Fecha em dezembro/2025 (10 <= 15)
     * - Período: 11/11/2025 a 10/12/2025
     * 
     * Exemplo: closing_day = 20, due_day = 15
     * - Fatura vence em dezembro/2025
     * - Fecha em novembro/2025 (20 > 15)
     * - Período: 21/10/2025 a 20/11/2025
     * 
     * @param int $year Ano do vencimento
     * @param int $month Mês do vencimento (1-12)
     * @return array [Carbon $start, Carbon $end] Período de fechamento
     */
    public function getStatementPeriodForDueMonth(int $year, int $month): array
    {
        $closingDay = (int) $this->closing_day;
        $dueDay = (int) $this->due_day;

        $dueMonth = Carbon::create($year, $month, 1)->startOfDay();
        $closingMonth = $closingDay <= $dueDay
            ? $dueMonth->copy()
            : $dueMonth->copy()->subMonthNoOverflow();

        $closingDay = max(1, min($closingDay, $closingMonth->daysInMonth));
        $closingDate = $closingMonth->copy()->day($closingDay)->endOfDay();

        $previousClosingMonth = $closingMonth->copy()->subMonthNoOverflow();
        $previousClosingDay = max(1, min($closingDay, $previousClosingMonth->daysInMonth));
        $previousClosingDate = $previousClosingMonth->copy()->day($previousClosingDay)->endOfDay();

        $start = $previousClosingDate->copy()->addDay()->startOfDay();
        $end = $closingDate->copy();

        return [$start, $end];
    }
}
