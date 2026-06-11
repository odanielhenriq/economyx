<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\InstallmentPurchaseService;
use Illuminate\Http\Request;

class InstallmentPurchaseWebController extends Controller
{
    public function index(Request $request, InstallmentPurchaseService $service)
    {
        $status = $request->query('status', 'active');

        validator(
            [
                'status' => $status,
                'credit_card_id' => $request->query('credit_card_id'),
                'category_id' => $request->query('category_id'),
                'purchase_from' => $request->query('purchase_from'),
                'purchase_to' => $request->query('purchase_to'),
            ],
            [
                'status' => 'nullable|in:active,completed,all',
                'credit_card_id' => 'nullable|integer|exists:credit_cards,id',
                'category_id' => 'nullable|integer|exists:categories,id',
                'purchase_from' => 'nullable|date',
                'purchase_to' => 'nullable|date|after_or_equal:purchase_from',
            ]
        )->validate();

        $data = $service->forUser($request->user(), [
            'status' => $status ?: 'active',
            'credit_card_id' => $request->query('credit_card_id'),
            'category_id' => $request->query('category_id'),
            'purchase_from' => $request->query('purchase_from'),
            'purchase_to' => $request->query('purchase_to'),
        ]);

        return view('installment-purchases.index', compact('data'));
    }
}
