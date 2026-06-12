<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CategoryBudget;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Services\InstallmentPurchaseService;
use App\Services\MonthlyDashboardService;
use App\Services\SharedExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyDashboardController extends Controller
{
    public function __construct(
        private MonthlyDashboardService $dashboardService,
        private InstallmentPurchaseService $installmentPurchases,
        private SharedExpenseService $sharedExpenses,
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

        $installmentData = $this->installmentPurchases->forUser($user, ['status' => 'active']);
        $sharedData = $this->sharedExpenses->forMonth($year, $month, $user, null, 'all');

        // Envia tudo para a view mensal
        return view('dashboard.monthly', [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $current->format('m/Y'),
            'prev' => ['year' => $prev->year, 'month' => $prev->month],
            'next' => ['year' => $next->year, 'month' => $next->month],
            'chartData' => $chartData,
            'previousMonthData' => $previousMonthData,
            'spendingByCategory' => $this->dashboardService->spendingByCategory($year, $month, $user),
            'hasTransactions' => Transaction::whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))->exists(),
            'hasCreditCards' => CreditCard::query()
                ->where(fn ($q) => $q->where('owner_user_id', $user->id)
                    ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $user->id)))
                ->exists(),
            'hasBudgets' => CategoryBudget::where('user_id', $user->id)->exists(),
            'followUpInstallments' => $installmentData['summary'] ?? [],
            'followUpShared' => [
                'has_shared_expenses' => $sharedData['has_shared_expenses'] ?? false,
                'has_partners' => $sharedData['has_partners'] ?? false,
                'pending_settlement' => $sharedData['summary']['pending_settlement'] ?? 0,
                'total_shared' => $sharedData['summary']['total_shared'] ?? 0,
            ],
        ]);
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
