<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CategoryBudget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Define o mês alvo (fallback para mês/ano atuais)
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        // Validação simples do range permitido
        validator(
            ['year' => $year, 'month' => $month],
            [
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12',
            ]
        )->validate();

        // Calcula mês anterior e próximo para navegação
        $current = Carbon::create($year, $month, 1);
        $prev = $current->copy()->subMonthNoOverflow();
        $next = $current->copy()->addMonthNoOverflow();

        // Envia tudo para a view mensal
        return view('dashboard.monthly', [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $current->format('m/Y'),
            'prev' => ['year' => $prev->year, 'month' => $prev->month],
            'next' => ['year' => $next->year, 'month' => $next->month],
            'chartData' => $this->getLast6MonthsSummary($year, $month),
            'budgetAlerts' => $this->getBudgetAlerts($year, $month),
            'spendingByCategory' => $this->getSpendingByCategory($year, $month),
            'upcomingDues' => $this->getUpcomingDues(),
        ]);
    }

    /**
     * Cruza orçamentos definidos pelo usuário com as despesas reais do mês.
     * Retorna apenas as categorias com orçamento que atingiram ≥ 80% do limite.
     */
    private function getBudgetAlerts(int $year, int $month): array
    {
        $user = Auth::user();
        $networkIds = $user->networkUsers()->pluck('id')->all();

        $budgets = CategoryBudget::where('user_id', $user->id)
            ->with('category')
            ->get();

        if ($budgets->isEmpty()) {
            return [];
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $alerts = [];

        foreach ($budgets as $budget) {
            $spent = Transaction::query()
                ->whereBetween('due_date', [$start, $end])
                ->where('category_id', $budget->category_id)
                ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
                ->whereHas('type', fn ($q) => $q->where('slug', 'dc'))
                ->sum('amount');

            $spent = round((float) $spent, 2);
            $limit = round((float) $budget->amount, 2);
            $percent = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;

            if ($percent >= 80) {
                $alerts[] = [
                    'category' => $budget->category->name,
                    'spent'    => $spent,
                    'limit'    => $limit,
                    'percent'  => $percent,
                    'over'     => $percent >= 100,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Retorna os top 6 gastos por categoria no mês, com percentual proporcional ao maior.
     */
    private function getSpendingByCategory(int $year, int $month): array
    {
        $user       = Auth::user();
        $networkIds = $user->networkUsers()->pluck('id')->all();

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $spending = Transaction::with('category')
            ->whereBetween('due_date', [$start, $end])
            ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn ($q) => $q->where('slug', 'dc'))
            ->whereNotNull('category_id')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($group) => [
                'category' => $group->first()->category?->name ?? 'Sem categoria',
                'total'    => round((float) $group->sum('amount'), 2),
            ])
            ->sortByDesc('total')
            ->take(6)
            ->values();

        $max = $spending->max('total') ?: 1;

        return $spending->map(fn ($item) => [
            ...$item,
            'percentage' => round(($item['total'] / $max) * 100),
        ])->toArray();
    }

    /**
     * Retorna despesas com due_date nos próximos 7 dias.
     */
    private function getUpcomingDues(): array
    {
        $user       = Auth::user();
        $networkIds = $user->networkUsers()->pluck('id')->all();

        $hoje   = now()->startOfDay();
        $limite = now()->addDays(7)->endOfDay();

        return Transaction::with('category')
            ->whereBetween('due_date', [$hoje, $limite])
            ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn ($q) => $q->where('slug', 'dc'))
            ->orderBy('due_date')
            ->get()
            ->map(fn ($t) => [
                'description' => $t->description,
                'due_date'    => $t->due_date?->format('Y-m-d'),
                'amount'      => round((float) $t->amount, 2),
                'days_left'   => (int) $hoje->diffInDays(Carbon::parse($t->due_date), false),
            ])
            ->toArray();
    }

    /**
     * Retorna receita e despesa total dos últimos 6 meses (incluindo o mês atual).
     * Usa somas diretas por tipo (slug 'rc' = receita, 'dc' = despesa).
     */
    private function getLast6MonthsSummary(int $year, int $month): array
    {
        $user = Auth::user();
        $networkIds = $user->networkUsers()->pluck('id')->all();

        $result = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::create($year, $month, 1)->subMonthsNoOverflow($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end   = $date->copy()->endOfMonth()->toDateString();

            $income = Transaction::query()
                ->whereBetween('due_date', [$start, $end])
                ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
                ->whereHas('type', fn ($q) => $q->where('slug', 'rc'))
                ->sum('amount');

            $expense = Transaction::query()
                ->whereBetween('due_date', [$start, $end])
                ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
                ->whereHas('type', fn ($q) => $q->where('slug', 'dc'))
                ->sum('amount');

            $result[] = [
                'label'   => $date->translatedFormat('M/y'),
                'income'  => round((float) $income, 2),
                'expense' => round((float) $expense, 2),
            ];
        }

        return $result;
    }
}
