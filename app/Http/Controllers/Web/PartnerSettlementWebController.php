<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PartnerSettlementService;
use Illuminate\Http\Request;

class PartnerSettlementWebController extends Controller
{
    public function index(Request $request, PartnerSettlementService $service)
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

        $settlement = $service->forMonth($year, $month, $request->user());

        return view('partner-settlements.index', [
            'settlement' => $settlement,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
