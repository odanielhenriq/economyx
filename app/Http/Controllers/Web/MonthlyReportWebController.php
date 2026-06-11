<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\MonthlyReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MonthlyReportWebController extends Controller
{
    public function pdf(Request $request, MonthlyReportService $reports)
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

        $report = $reports->build($year, $month, $request->user());
        $pdf = Pdf::loadView('reports.monthly-pdf', compact('report'))
            ->setPaper('a4');

        return $pdf->download($reports->filename($year, $month));
    }
}
