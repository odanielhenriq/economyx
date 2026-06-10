<?php

namespace App\Services;

use App\Models\CategoryBudget;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Models\User;
use Carbon\Carbon;

class FinancialAlertService
{
    public function forDashboard(int $year, int $month, User $user, int $limit = 5): array
    {
        $all = $this->collect($year, $month, $user);
        $total = count($all);
        $items = array_slice($all, 0, $limit);

        return [
            'items' => $items,
            'total' => $total,
            'visible_count' => count($items),
            'has_more' => $total > $limit,
            'more_count' => max(0, $total - $limit),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function collect(int $year, int $month, User $user): array
    {
        $networkIds = $user->networkUsers()->pluck('id')->all();
        $alerts = [];

        $alerts = array_merge(
            $alerts,
            $this->budgetAlerts($year, $month, $user, $networkIds),
            $this->invoiceDueAlerts($year, $month, $user),
            $this->transactionDueAlerts($year, $month, $networkIds),
            $this->cardUsageAlerts($year, $month, $user, $networkIds),
            $this->installmentEndingAlerts($year, $month, $networkIds),
        );

        usort($alerts, function (array $a, array $b) {
            if ($a['priority'] === $b['priority']) {
                return strcmp($a['type'], $b['type']);
            }

            return $b['priority'] <=> $a['priority'];
        });

        return $alerts;
    }

    /**
     * Formato legado usado pelo export JSON analítico.
     *
     * @param  array<int, array<string, mixed>>  $alerts
     * @return array<int, array<string, mixed>>
     */
    public function toExportLegacy(array $alerts): array
    {
        $legacy = [];

        foreach ($alerts as $alert) {
            $legacy[] = match ($alert['type']) {
                'budget_warning', 'budget_reached', 'budget_exceeded' => [
                    'tipo' => 'orcamento_proximo_limite',
                    'categoria' => $alert['meta']['category'] ?? '',
                    'percentual' => $alert['meta']['percent'] ?? 0,
                    'mensagem' => $alert['message'],
                ],
                'card_high_usage' => [
                    'tipo' => 'cartao_alto_uso',
                    'cartao' => $alert['meta']['card'] ?? '',
                    'percentual_uso' => $alert['meta']['percent'] ?? 0,
                    'mensagem' => $alert['message'],
                ],
                'installment_ending' => [
                    'tipo' => 'parcela_proxima',
                    'descricao' => $alert['meta']['description'] ?? '',
                    'numero' => $alert['meta']['installment_number'] ?? null,
                    'data_vencimento' => $alert['meta']['due_date'] ?? null,
                    'valor' => $alert['meta']['amount'] ?? null,
                    'dias_restantes' => $alert['meta']['days_left'] ?? null,
                    'origem' => [
                        'tipo' => 'parcela',
                        'transaction_id' => $alert['meta']['transaction_id'] ?? null,
                        'installment_id' => $alert['meta']['installment_id'] ?? null,
                    ],
                ],
                default => [
                    'tipo' => $alert['type'],
                    'mensagem' => $alert['message'],
                ],
            };
        }

        return $legacy;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function budgetAlerts(int $year, int $month, User $user, array $networkIds): array
    {
        $alerts = [];
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        foreach (CategoryBudget::where('user_id', $user->id)->with('category')->get() as $budget) {
            $spent = $this->categorySpentInMonth($budget->category_id, $start, $end, $networkIds);
            $limit = round((float) $budget->amount, 2);

            if ($limit <= 0) {
                continue;
            }

            $percent = round(($spent / $limit) * 100, 1);

            if ($percent < 80) {
                continue;
            }

            $categoryName = $budget->category->name ?? 'Categoria';
            $meta = [
                'category' => $categoryName,
                'percent' => $percent,
                'spent' => $spent,
                'limit' => $limit,
            ];

            if ($percent > 100) {
                $over = round($spent - $limit, 2);
                $alerts[] = $this->makeAlert(
                    type: 'budget_exceeded',
                    severity: 'danger',
                    title: 'Orçamento ultrapassado',
                    message: "Você ultrapassou em {$this->formatMoney($over)} o orçamento de {$categoryName}.",
                    actionLabel: 'Ver orçamento',
                    url: route('budgets.index'),
                    priority: 180,
                    meta: $meta,
                );
            } elseif ($percent >= 100) {
                $alerts[] = $this->makeAlert(
                    type: 'budget_reached',
                    severity: 'danger',
                    title: 'Orçamento atingido',
                    message: "Você atingiu o orçamento de {$categoryName}.",
                    actionLabel: 'Ver orçamento',
                    url: route('budgets.index'),
                    priority: 170,
                    meta: $meta,
                );
            } else {
                $alerts[] = $this->makeAlert(
                    type: 'budget_warning',
                    severity: 'warning',
                    title: 'Orçamento em atenção',
                    message: "Você já usou {$percent}% do orçamento de {$categoryName}.",
                    actionLabel: 'Ver orçamento',
                    url: route('budgets.index'),
                    priority: 140,
                    meta: $meta,
                );
            }
        }

        return $alerts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function invoiceDueAlerts(int $year, int $month, User $user): array
    {
        if (! $this->isCurrentMonth($year, $month)) {
            return [];
        }

        $today = now()->startOfDay();
        $cardIds = $this->userCardIds($user);
        $alerts = [];

        $statements = CreditCardStatement::with('creditCard')
            ->whereIn('credit_card_id', $cardIds)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($statements as $statement) {
            $dueDay = $statement->due_day ?: $statement->creditCard?->due_day ?: 1;
            $dueDate = $this->statementDueDate($year, $month, (int) $dueDay)->startOfDay();
            $daysLeft = (int) $today->diffInDays($dueDate, false);
            $cardName = $statement->creditCard?->name ?? 'Cartão';

            if ($daysLeft < 0) {
                $daysOverdue = abs($daysLeft);
                $message = $daysOverdue === 1
                    ? "A fatura do {$cardName} venceu ontem."
                    : "A fatura do {$cardName} venceu há {$daysOverdue} dias.";

                $alerts[] = $this->makeAlert(
                    type: 'invoice_overdue',
                    severity: 'danger',
                    title: 'Fatura vencida',
                    message: $message,
                    actionLabel: 'Ver fatura',
                    url: route('cards.statement.index'),
                    priority: 200,
                    meta: ['card' => $cardName, 'days_left' => $daysLeft, 'due_date' => $dueDate->toDateString()],
                );
            } elseif ($daysLeft === 0) {
                $alerts[] = $this->makeAlert(
                    type: 'invoice_due_today',
                    severity: 'danger',
                    title: 'Fatura vence hoje',
                    message: "A fatura do {$cardName} vence hoje.",
                    actionLabel: 'Ver fatura',
                    url: route('cards.statement.index'),
                    priority: 190,
                    meta: ['card' => $cardName, 'days_left' => 0, 'due_date' => $dueDate->toDateString()],
                );
            } elseif ($daysLeft <= 7) {
                $alerts[] = $this->makeAlert(
                    type: 'invoice_due_soon',
                    severity: 'warning',
                    title: 'Fatura próxima do vencimento',
                    message: "A fatura do {$cardName} vence em {$daysLeft} ".($daysLeft === 1 ? 'dia' : 'dias').'.',
                    actionLabel: 'Ver fatura',
                    url: route('cards.statement.index'),
                    priority: 160,
                    meta: ['card' => $cardName, 'days_left' => $daysLeft, 'due_date' => $dueDate->toDateString()],
                );
            }
        }

        return $alerts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transactionDueAlerts(int $year, int $month, array $networkIds): array
    {
        if (! $this->isCurrentMonth($year, $month)) {
            return [];
        }

        $today = now()->startOfDay();
        $installmentParentIds = TransactionInstallment::query()
            ->select('transaction_id')
            ->distinct()
            ->pluck('transaction_id')
            ->all();

        $baseQuery = Transaction::query()
            ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn ($q) => $q->where('slug', 'dc'))
            ->whereNull('credit_card_id');

        if (! empty($installmentParentIds)) {
            $baseQuery->whereNotIn('id', $installmentParentIds);
        }

        $overdueCount = (clone $baseQuery)
            ->whereDate('due_date', '<', $today->toDateString())
            ->count();

        $dueTodayCount = (clone $baseQuery)
            ->whereDate('due_date', $today->toDateString())
            ->count();

        $upcomingCount = (clone $baseQuery)
            ->whereDate('due_date', '>', $today->toDateString())
            ->whereDate('due_date', '<=', $today->copy()->addDays(7)->toDateString())
            ->count();

        $alerts = [];

        if ($overdueCount > 0) {
            $alerts[] = $this->makeAlert(
                type: 'transactions_overdue',
                severity: 'danger',
                title: 'Contas vencidas',
                message: $overdueCount === 1
                    ? 'Você tem 1 conta vencida.'
                    : "Você tem {$overdueCount} contas vencidas.",
                actionLabel: 'Ver transações',
                url: route('transactions.index'),
                priority: 195,
                meta: ['count' => $overdueCount],
            );
        }

        if ($dueTodayCount > 0) {
            $alerts[] = $this->makeAlert(
                type: 'transactions_due_today',
                severity: 'danger',
                title: 'Contas vencem hoje',
                message: $dueTodayCount === 1
                    ? 'Você tem 1 conta vencendo hoje.'
                    : "Você tem {$dueTodayCount} contas vencendo hoje.",
                actionLabel: 'Ver transações',
                url: route('transactions.index'),
                priority: 192,
                meta: ['count' => $dueTodayCount],
            );
        }

        if ($upcomingCount > 0) {
            $alerts[] = $this->makeAlert(
                type: 'transactions_due_soon',
                severity: 'warning',
                title: 'Contas nos próximos dias',
                message: $upcomingCount === 1
                    ? 'Você tem 1 conta para pagar nos próximos 7 dias.'
                    : "Você tem {$upcomingCount} contas para pagar nos próximos 7 dias.",
                actionLabel: 'Ver transações',
                url: route('transactions.index'),
                priority: 158,
                meta: ['count' => $upcomingCount],
            );
        }

        return $alerts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cardUsageAlerts(int $year, int $month, User $user, array $networkIds): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $alerts = [];

        foreach (CreditCard::whereHas('users', fn ($q) => $q->where('users.id', $user->id))->get() as $card) {
            if ((float) $card->limit <= 0) {
                continue;
            }

            $spent = (float) Transaction::where('credit_card_id', $card->id)
                ->whereBetween('transaction_date', [$start, $end])
                ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
                ->sum('amount');

            $percent = round(($spent / (float) $card->limit) * 100, 1);

            if ($percent < 75) {
                continue;
            }

            $alerts[] = $this->makeAlert(
                type: 'card_high_usage',
                severity: 'warning',
                title: 'Cartão com alto uso',
                message: "O cartão {$card->name} já usou {$percent}% do limite cadastrado.",
                actionLabel: 'Ver cartão',
                url: route('credit-cards.index'),
                priority: 150,
                meta: ['card' => $card->name, 'percent' => $percent],
            );
        }

        return $alerts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function installmentEndingAlerts(int $year, int $month, array $networkIds): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();
        $today = now()->startOfDay();

        if ($this->isCurrentMonth($year, $month)) {
            $rangeStart = $today;
            $rangeEnd = $today->copy()->addDays(30)->endOfDay();
        } else {
            $rangeStart = $monthStart;
            $rangeEnd = $monthEnd;
        }

        $installments = TransactionInstallment::with('transaction')
            ->whereColumn('installment_number', 'installment_total')
            ->where('installment_total', '>=', 2)
            ->whereBetween('due_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->whereHas('transaction', function ($q) use ($networkIds) {
                $q->whereHas('users', fn ($sub) => $sub->whereIn('users.id', $networkIds));
            })
            ->get();

        $alerts = [];

        foreach ($installments as $installment) {
            $description = $installment->transaction?->description ?: 'Compra parcelada';
            $dueDate = Carbon::parse($installment->due_date)->startOfDay();
            $daysLeft = (int) $today->diffInDays($dueDate, false);

            $message = $dueDate->isSameMonth($today) && $this->isCurrentMonth($year, $month)
                ? "A compra \"{$description}\" termina este mês."
                : 'Uma compra parcelada termina nos próximos 30 dias.';

            $alerts[] = $this->makeAlert(
                type: 'installment_ending',
                severity: 'info',
                title: 'Parcela terminando',
                message: $message,
                actionLabel: 'Ver transações',
                url: route('transactions.index'),
                priority: 130,
                meta: [
                    'description' => $description,
                    'installment_number' => (int) $installment->installment_number,
                    'due_date' => $dueDate->toDateString(),
                    'amount' => round((float) $installment->amount, 2),
                    'days_left' => $daysLeft,
                    'transaction_id' => $installment->transaction_id,
                    'installment_id' => $installment->id,
                ],
            );
        }

        return $alerts;
    }

    private function categorySpentInMonth(int $categoryId, string $start, string $end, array $networkIds): float
    {
        $direct = (float) Transaction::query()
            ->whereBetween('due_date', [$start, $end])
            ->where('category_id', $categoryId)
            ->whereNull('installment_total')
            ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn ($q) => $q->where('slug', 'dc'))
            ->sum('amount');

        $installments = (float) TransactionInstallment::query()
            ->whereBetween('due_date', [$start, $end])
            ->whereHas('transaction', function ($q) use ($categoryId, $networkIds) {
                $q->where('category_id', $categoryId)
                    ->whereHas('users', fn ($sub) => $sub->whereIn('users.id', $networkIds))
                    ->whereHas('type', fn ($sub) => $sub->where('slug', 'dc'));
            })
            ->sum('amount');

        return round($direct + $installments, 2);
    }

    private function userCardIds(User $user): array
    {
        return CreditCard::query()
            ->where(fn ($q) => $q->where('owner_user_id', $user->id)
                ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $user->id)))
            ->pluck('id')
            ->all();
    }

    private function statementDueDate(int $year, int $month, int $dueDay): Carbon
    {
        $base = Carbon::create($year, $month, 1)->startOfDay();
        $day = min(max($dueDay, 1), $base->daysInMonth);

        return $base->copy()->day($day);
    }

    private function isCurrentMonth(int $year, int $month): bool
    {
        $now = now();

        return $year === (int) $now->year && $month === (int) $now->month;
    }

    private function formatMoney(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function makeAlert(
        string $type,
        string $severity,
        string $title,
        string $message,
        string $actionLabel,
        string $url,
        int $priority,
        array $meta = [],
    ): array {
        return [
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'action_label' => $actionLabel,
            'url' => $url,
            'priority' => $priority,
            'meta' => $meta,
        ];
    }
}
