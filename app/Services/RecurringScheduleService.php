<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use Carbon\Carbon;

class RecurringScheduleService
{
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

    private function clampDay(int $year, int $month, int $day): int
    {
        $base = Carbon::create($year, $month, 1);

        return min(max($day, 1), $base->daysInMonth);
    }
}
