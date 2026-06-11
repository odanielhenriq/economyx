<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SharedExpenseService;
use App\Services\SharedExpenseSettlementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SharedExpenseWebController extends Controller
{
    public function index(Request $request, SharedExpenseService $service)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $partnerId = $request->query('partner_id');
        $status = $request->query('status', 'all');

        validator(
            [
                'year' => $year,
                'month' => $month,
                'partner_id' => $partnerId,
                'status' => $status,
            ],
            [
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12',
                'partner_id' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|in:all,pending,settled',
            ]
        )->validate();

        $data = $service->forMonth(
            $year,
            $month,
            $request->user(),
            $partnerId ? (int) $partnerId : null,
            $status ?: 'all'
        );

        return view('shared-expenses.index', [
            'data' => $data,
            'year' => $year,
            'month' => $month,
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function settle(
        Request $request,
        Transaction $transaction,
        User $participant,
        SharedExpenseSettlementService $settlements
    ) {
        try {
            $settlements->settle($transaction, $participant, $request->user());
        } catch (AuthorizationException|InvalidArgumentException $exception) {
            return $this->settlementErrorResponse($request, $exception->getMessage(), 403);
        }

        return $this->settlementSuccessResponse($request, 'Parte marcada como acertada.');
    }

    public function unsettle(
        Request $request,
        Transaction $transaction,
        User $participant,
        SharedExpenseSettlementService $settlements
    ) {
        try {
            $settlements->unsettle($transaction, $participant, $request->user());
        } catch (AuthorizationException|InvalidArgumentException $exception) {
            return $this->settlementErrorResponse($request, $exception->getMessage(), 403);
        }

        return $this->settlementSuccessResponse($request, 'Acerto desfeito.');
    }

    private function settlementSuccessResponse(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function settlementErrorResponse(Request $request, string $message, int $status)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with('error', $message);
    }
}
