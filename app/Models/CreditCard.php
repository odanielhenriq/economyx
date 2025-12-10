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

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'credit_card_user',
            'credit_card_id',
            'user_id'
        );
    }

    public function getBillingPeriodFor(int $year, int $month): array
    {
        $closingDay = (int) $this->closing_day;

        // Data de fechamento do mês (ex.: 2025-12-05 23:59:59)
        $closingDate = Carbon::create($year, $month, $closingDay)->endOfDay();

        // Data de fechamento anterior (ex.: 2025-11-05 23:59:59)
        $previousClosingDate = $closingDate->copy()->subMonth();

        // Período:
        //  - início: dia seguinte ao fechamento anterior (ex.: 06/11)
        //  - fim: dia do fechamento atual (ex.: 05/12)
        $start = $previousClosingDate->copy()->addDay()->startOfDay();
        $end   = $closingDate->copy();

        return [$start, $end];
    }
}
