<?php

namespace App\Services;

use App\Models\MonthlySavingsGoal;
use App\Models\User;

class MonthlySavingsGoalService
{
    public function forDashboard(User $user, int $year, int $month, float $projectedBalance): array
    {
        $goal = MonthlySavingsGoal::query()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $goal) {
            return [
                'exists' => false,
                'message' => 'Defina quanto você quer guardar este mês.',
            ];
        }

        return $this->buildPayload($goal, $projectedBalance);
    }

    public function upsert(User $user, int $year, int $month, float $targetAmount, ?string $note = null): MonthlySavingsGoal
    {
        return MonthlySavingsGoal::updateOrCreate(
            [
                'user_id' => $user->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'target_amount' => round($targetAmount, 2),
                'note' => $note,
            ]
        );
    }

    public function findForUser(User $user, int $year, int $month): ?MonthlySavingsGoal
    {
        return MonthlySavingsGoal::query()
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    private function buildPayload(MonthlySavingsGoal $goal, float $projectedBalance): array
    {
        $target = round((float) $goal->target_amount, 2);
        $projected = round($projectedBalance, 2);
        $difference = round($projected - $target, 2);
        $onTrack = $projected >= $target;
        $progressPercent = $target > 0
            ? min(100, max(0, round(($projected / $target) * 100, 2)))
            : 0;

        return [
            'exists' => true,
            'target_amount' => $target,
            'projected_balance' => $projected,
            'difference' => $difference,
            'progress_percent' => $progressPercent,
            'status' => $onTrack ? 'on_track' : 'attention',
            'status_label' => $onTrack ? 'No caminho certo' : 'Atenção',
            'message' => $this->humanMessage($difference, $onTrack),
            'note' => $goal->note,
            'year' => (int) $goal->year,
            'month' => (int) $goal->month,
        ];
    }

    private function humanMessage(float $difference, bool $onTrack): string
    {
        $formatted = number_format(abs($difference), 2, ',', '.');

        if ($onTrack) {
            if ($difference > 0) {
                return "Você está R$ {$formatted} acima da meta.";
            }

            return 'Você atingiu sua meta de economia.';
        }

        return "Faltam R$ {$formatted} para atingir sua meta.";
    }
}
