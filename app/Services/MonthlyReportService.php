<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class MonthlyReportService
{
    private const INSTALLMENT_LIMIT = 10;

    public function __construct(
        private MonthlyDashboardService $dashboardService,
        private FinancialAlertService $alerts,
        private InstallmentPurchaseService $installmentPurchases,
        private SharedExpenseService $sharedExpenses,
    ) {}

    public function build(int $year, int $month, User $user): array
    {
        Carbon::setLocale('pt_BR');

        $dashboard = $this->dashboardService->build($year, $month, $user);
        $cards = $dashboard['cards'];
        $lists = $dashboard['lists'];
        $projected = $cards['projected_balance'] ?? [];
        $savingsGoal = $dashboard['savings_goal'] ?? ['exists' => false];
        $period = Carbon::create($year, $month, 1)->locale('pt_BR');
        $periodEnd = $period->copy()->endOfMonth();

        $partners = $user->networkUsers()
            ->reject(fn (User $member) => $member->id === $user->id)
            ->pluck('name')
            ->values()
            ->all();

        $prevPeriod = $period->copy()->subMonthNoOverflow();
        $prevSummary = $this->dashboardService->summaryForMonth($prevPeriod->year, $prevPeriod->month, $user);

        $incomeTotal = round((float) ($cards['income_total_month'] ?? 0), 2);
        $expenseTotal = round((float) ($cards['expense_total_month'] ?? 0), 2);
        $balanceTotal = round((float) ($cards['balance_month'] ?? 0), 2);
        $payableTotal = round((float) ($cards['payable_total_month'] ?? 0), 2);
        $projectedBalance = round((float) ($projected['amount'] ?? 0), 2);

        $incomeVariation = $this->percentVariation($incomeTotal, (float) ($prevSummary['income'] ?? 0));
        $expenseVariation = $this->percentVariation($expenseTotal, (float) ($prevSummary['expense'] ?? 0));

        $categoryItems = $this->dashboardService->spendingByCategoryItems($year, $month, $user);
        $categoryTotal = round(collect($categoryItems)->sum('total'), 2);
        $categories = collect($categoryItems)
            ->map(function (array $item) use ($categoryTotal) {
                $share = $categoryTotal > 0
                    ? round(($item['total'] / $categoryTotal) * 100, 1)
                    : 0.0;

                return [
                    'category' => $item['category'],
                    'total' => $item['total'],
                    'share_percent' => $share,
                ];
            })
            ->values()
            ->all();

        $topCategory = $categories[0] ?? null;

        $alertItems = collect($this->alerts->collect($year, $month, $user))
            ->map(fn (array $alert) => [
                'title' => $alert['title'] ?? 'Alerta',
                'message' => $alert['message'] ?? '',
                'severity' => $alert['severity'] ?? 'info',
            ])
            ->values()
            ->all();

        $installmentData = $this->installmentPurchases->forUser($user, ['status' => 'active']);
        $activeInstallments = collect($installmentData['items'] ?? [])
            ->filter(fn (array $item) => ($item['status'] ?? '') !== 'completed')
            ->values();
        $installmentOverflow = max(0, $activeInstallments->count() - self::INSTALLMENT_LIMIT);

        $shared = $this->sharedExpenses->forMonth($year, $month, $user, null, 'all');
        $sharedSummary = null;
        if ($shared['has_shared_expenses'] ?? false) {
            $sharedSummary = [
                'total_shared' => $shared['summary']['total_shared'] ?? 0,
                'pending_settlement' => $shared['summary']['pending_settlement'] ?? 0,
                'settled_total' => $shared['summary']['settled_total'] ?? 0,
                'suggestions' => collect($shared['suggestions'] ?? [])
                    ->take(3)
                    ->pluck('message')
                    ->values()
                    ->all(),
            ];
        }

        $summary = [
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'balance_total' => $balanceTotal,
            'payable_total' => $payableTotal,
            'projected_balance' => $projectedBalance,
            'projected_is_negative' => (bool) ($projected['is_negative'] ?? false),
        ];

        return [
            'header' => [
                'user_name' => $user->name,
                'month_label' => ucfirst($period->translatedFormat('F \d\e Y')),
                'month_label_short' => $period->translatedFormat('M/Y'),
                'period_range' => sprintf(
                    '%s — %s',
                    $period->format('d/m/Y'),
                    $periodEnd->format('d/m/Y')
                ),
                'generated_at' => now()->format('d/m/Y H:i'),
                'generated_date' => now()->format('d/m/Y'),
                'network_label' => $this->networkLabel($user->name, $partners),
                'has_network' => count($partners) > 0,
            ],
            'executive_summary' => $this->buildExecutiveSummary(
                $user,
                $period,
                $summary,
                $savingsGoal,
                $topCategory,
                count($alertItems),
                $partners,
            ),
            'comparison' => [
                'previous_month_label' => ucfirst($prevPeriod->translatedFormat('F/Y')),
                'income_variation' => $incomeVariation,
                'expense_variation' => $expenseVariation,
            ],
            'summary' => $summary,
            'projected_breakdown' => [
                'income' => round((float) ($projected['income'] ?? 0), 2),
                'expenses_recorded' => round((float) ($projected['expenses_recorded'] ?? 0), 2),
                'payable' => round((float) ($projected['payable'] ?? 0), 2),
                'recurring_projection' => round((float) ($projected['recurring_projection'] ?? 0), 2),
                'total' => $projectedBalance,
            ],
            'payables' => [
                'cards_total' => round((float) ($cards['breakdown']['payable_cards_total'] ?? 0), 2),
                'loans_total' => round((float) ($cards['breakdown']['payable_loans_total'] ?? 0), 2),
                'cards' => collect($lists['payables_cards'] ?? [])->values()->all(),
                'loans' => collect($lists['payables_loans'] ?? [])->values()->all(),
            ],
            'savings_goal' => $savingsGoal,
            'alerts' => $alertItems,
            'categories' => $categories,
            'categories_total' => $categoryTotal,
            'top_category' => $topCategory,
            'future_commitments' => $dashboard['future_commitments']['months'] ?? [],
            'future_commitments_note' => $dashboard['future_commitments']['note'] ?? null,
            'installment_purchases' => $activeInstallments->take(self::INSTALLMENT_LIMIT)->values()->all(),
            'installment_purchases_overflow' => $installmentOverflow,
            'shared_expenses' => $sharedSummary,
        ];
    }

    public function filename(int $year, int $month): string
    {
        return sprintf('economyx-relatorio-%04d-%02d.pdf', $year, $month);
    }

    /**
     * @param  list<string>  $partners
     * @return list<string>
     */
    private function buildExecutiveSummary(
        User $user,
        Carbon $period,
        array $summary,
        array $savingsGoal,
        ?array $topCategory,
        int $alertCount,
        array $partners,
    ): array {
        $monthName = ucfirst($period->translatedFormat('F'));
        $lines = [];

        $lines[] = sprintf(
            'Olá, %s. Este relatório resume suas finanças de %s com base nos lançamentos da sua rede no Economyx.',
            $user->name,
            $monthName,
        );

        if (count($partners) > 0) {
            $lines[] = sprintf(
                'Os valores consideram você e %s.',
                $this->joinNames($partners),
            );
        }

        if ($summary['income_total'] > 0 || $summary['expense_total'] > 0) {
            $lines[] = sprintf(
                'No período, você registrou R$ %s em receitas e R$ %s em despesas, com saldo de R$ %s.',
                $this->money($summary['income_total']),
                $this->money($summary['expense_total']),
                $this->money($summary['balance_total']),
            );
        } else {
            $lines[] = 'Não há lançamentos registrados neste mês — o relatório reflete apenas o que está cadastrado no sistema.';
        }

        $projectedTone = $summary['projected_is_negative'] ? 'negativo' : 'positivo';
        $lines[] = sprintf(
            'O saldo projetado para o fim do mês é %s (R$ %s), considerando faturas, parcelas e contas fixas previstas.',
            $projectedTone,
            $this->money(abs($summary['projected_balance'])),
        );

        if ($savingsGoal['exists'] ?? false) {
            $lines[] = sprintf(
                'Sua meta de economia é R$ %s. Status: %s. %s',
                $this->money((float) ($savingsGoal['target_amount'] ?? 0)),
                $savingsGoal['status_label'] ?? '',
                $savingsGoal['message'] ?? '',
            );
        }

        if ($topCategory) {
            $lines[] = sprintf(
                'A categoria com maior gasto foi %s (R$ %s, %s%% do total).',
                $topCategory['category'],
                $this->money((float) $topCategory['total']),
                number_format((float) $topCategory['share_percent'], 1, ',', '.'),
            );
        }

        if ($alertCount > 0) {
            $lines[] = sprintf(
                'Há %d %s que merecem atenção neste mês — veja a seção de alertas abaixo.',
                $alertCount,
                $alertCount === 1 ? 'ponto' : 'pontos',
            );
        } else {
            $lines[] = 'Nenhum alerta importante foi identificado para este mês.';
        }

        return $lines;
    }

    /**
     * @param  list<string>  $partners
     */
    private function networkLabel(string $userName, array $partners): string
    {
        if ($partners === []) {
            return $userName;
        }

        return $userName.' e '.$this->joinNames($partners);
    }

    /**
     * @param  list<string>  $names
     */
    private function joinNames(array $names): string
    {
        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);

        return implode(', ', $names).' e '.$last;
    }

    private function percentVariation(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }
}
