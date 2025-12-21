<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MonthlyDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyDashboardController extends Controller
{
    public function index(Request $request, MonthlyDashboardService $service)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        validator(
            ['year' => $year, 'month' => $month],
            [
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12',
            ]
        )->validate();

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $user = $request->user();

        if (! $user) {
            $userId = $request->query('user_id');
            $user = $userId ? User::find($userId) : User::query()->orderBy('id')->first();
        }

        if (! $user) {
            return response()->json([
                'message' => 'Nenhum usuário disponível para gerar o dashboard.',
            ], 422);
        }

        $payload = $service->build($year, $month, $user);

        return response()->json([
            'year' => $year,
            'month' => $month,
            'range' => [
                'start' => $monthStart->toDateString(),
                'end' => $monthEnd->toDateString(),
            ],
            'cards' => $payload['cards'],
            'lists' => $payload['lists'],
        ]);
    }
}
