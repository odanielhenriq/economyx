<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Http\Request;

class CardStatementController extends Controller
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {}

    public function show(Request $request, int $cardId)
    {
        $year  = (int) ($request->query('year')  ?? now()->year);
        $month = (int) ($request->query('month') ?? now()->month);

        $result = $this->transactions->getCardBill($cardId, $year, $month);

        return response()->json([
            'card' => [
                'id'    => $result['card']->id,
                'name'  => $result['card']->name,
                'owner' => $result['card']->owner->name ?? null,
                'closing_day' => $result['card']->closing_day,
                'due_day'     => $result['card']->due_day,
            ],
            'period' => [
                'start' => $result['period']['start']->toDateString(),
                'end'   => $result['period']['end']->toDateString(),
            ],
            'summary' => $result['summary'],
            'transactions' => TransactionResource::collection($result['transactions']),
        ]);
    }
}
