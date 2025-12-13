<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
     * Retorna o período de faturamento de um ano/mês:
     *  - start = dia seguinte ao fechamento anterior
     *  - end   = dia do fechamento atual
     *
     * Ex.: fechamento dia 5
     *      billing 12/2025 → 06/11/2025 a 05/12/2025
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
}
