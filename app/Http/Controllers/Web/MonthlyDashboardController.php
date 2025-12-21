<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        ]);
    }
}
