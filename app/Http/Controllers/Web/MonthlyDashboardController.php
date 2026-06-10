<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CategoryBudget;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\TransactionInstallment;
use App\Services\MonthlyDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyDashboardController extends Controller
{
    public function __construct(
        private MonthlyDashboardService $dashboardService
    ) {}

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

        $chartData = $this->getLast6MonthsSummary($year, $month);
        // Índice 4 = mês anterior (índice 5 = mês atual)
        $previousMonthData = $chartData[count($chartData) - 2] ?? null;

        $user = Auth::user();
        $networkIds = $user->networkUsers()->pluck('id')->all();

        // Envia tudo para a view mensal
        return view('dashboard.monthly', [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $current->format('m/Y'),
            'prev' => ['year' => $prev->year, 'month' => $prev->month],
            'next' => ['year' => $next->year, 'month' => $next->month],
            'chartData' => $chartData,
            'previousMonthData' => $previousMonthData,
            'spendingByCategory' => $this->getSpendingByCategory($year, $month),
            'hasTransactions' => Transaction::whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))->exists(),
            'hasCreditCards' => CreditCard::query()
                ->where(fn ($q) => $q->where('owner_user_id', $user->id)
                    ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $user->id)))
                ->exists(),
            'hasBudgets' => CategoryBudget::where('user_id', $user->id)->exists(),
        ]);
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

        $direct = Transaction::with('category')
            ->whereBetween('due_date', [$start, $end])
            ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
            ->whereHas('type', fn ($q) => $q->where('slug', 'dc'))
            ->whereNotNull('category_id')
            ->whereNull('installment_total')
            ->get();

        $installmentRows = TransactionInstallment::with('transaction.category')
            ->whereBetween('due_date', [$start, $end])
            ->whereHas('transaction', function ($q) use ($networkIds) {
                $q->whereHas('users', fn ($sub) => $sub->whereIn('users.id', $networkIds))
                    ->whereHas('type', fn ($sub) => $sub->where('slug', 'dc'));
            })
            ->get();

        $items = collect();

        foreach ($direct as $tx) {
            $items->push([
                'category_id' => $tx->category_id,
                'category' => $tx->category?->name ?? 'Sem categoria',
                'amount' => (float) $tx->amount,
            ]);
        }

        foreach ($installmentRows as $inst) {
            $tx = $inst->transaction;
            if (! $tx?->category_id) {
                continue;
            }
            $items->push([
                'category_id' => $tx->category_id,
                'category' => $tx->category?->name ?? 'Sem categoria',
                'amount' => (float) $inst->amount,
            ]);
        }

        $spending = $items->groupBy('category_id')
            ->map(fn ($group) => [
                'category' => $group->first()['category'],
                'total' => round((float) $group->sum('amount'), 2),
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
     * Retorna receita e despesa total dos últimos 6 meses (incluindo o mês atual).
     * Usa somas diretas por tipo (slug 'rc' = receita, 'dc' = despesa).
     */
    private function getLast6MonthsSummary(int $year, int $month): array
    {
        $user = Auth::user();
        $result = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::create($year, $month, 1)->subMonthsNoOverflow($i);
            $summary = $this->dashboardService->summaryForMonth($date->year, $date->month, $user);

            $result[] = [
                'label' => $date->translatedFormat('M/y'),
                'income' => $summary['income'],
                'expense' => $summary['expense'],
            ];
        }

        return $result;
    }
}
