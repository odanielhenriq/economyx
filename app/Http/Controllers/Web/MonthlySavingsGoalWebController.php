<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\MonthlySavingsGoalService;
use Illuminate\Http\Request;

class MonthlySavingsGoalWebController extends Controller
{
    public function upsert(Request $request, MonthlySavingsGoalService $service)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'target_amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:500',
        ]);

        $service->upsert(
            $request->user(),
            (int) $data['year'],
            (int) $data['month'],
            (float) $data['target_amount'],
            $data['note'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Meta de economia salva.']);
        }

        return redirect()
            ->route('dashboard.monthly', [
                'year' => $data['year'],
                'month' => $data['month'],
            ])
            ->with('success', 'Meta de economia salva.');
    }
}
