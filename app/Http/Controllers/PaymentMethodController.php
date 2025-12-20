<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Repositories\PaymentMethodRepositoryInterface;

class PaymentMethodController extends Controller
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethods
    ) {}

    public function index()
    {
        try {
            $paymentMethods = $this->paymentMethods->getAll();

            return PaymentMethodResource::collection($paymentMethods);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve payment methods',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(PaymentMethodRequest $request)
    {
        try {
            $data = $request->validated();

            $paymentMethod = $this->paymentMethods->create($data);

            return (new PaymentMethodResource($paymentMethod))
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to create payment method',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $paymentMethod = $this->paymentMethods->findById((int) $id);

            if (! $paymentMethod) {
                return response()->json([
                    'error' => 'Payment method not found',
                ], 404);
            }

            return (new PaymentMethodResource($paymentMethod))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve payment method',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(PaymentMethodRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $paymentMethod = $this->paymentMethods->update((int) $id, $data);

            if (! $paymentMethod) {
                return response()->json([
                    'error' => 'Payment method not found',
                ], 404);
            }

            return (new PaymentMethodResource($paymentMethod))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to update payment method',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $deleted = $this->paymentMethods->delete((int) $id);

            if (! $deleted) {
                return response()->json([
                    'error' => 'Payment method not found',
                ], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete payment method',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
