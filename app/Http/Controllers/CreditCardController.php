<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreditCardRequest;
use App\Http\Resources\CreditCardResource;
use App\Models\User;
use App\Repositories\CreditCardRepositoryInterface;

class CreditCardController extends Controller
{
    public function __construct(
        private CreditCardRepositoryInterface $creditCards
    ) {}

    public function index()
    {
        try {
            $creditCards = $this->creditCards->getAll();

            return CreditCardResource::collection($creditCards);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve credit cards',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(CreditCardRequest $request)
    {
        try {
            $data = $request->validated();

            $data['is_shared'] = $request->boolean('is_shared');

            if (! empty($data['owner_user_id'])) {
                $owner = User::find((int) $data['owner_user_id']);
                $data['owner_name'] = $owner?->name;
            }

            $sharedUserIds = $data['shared_user_ids'] ?? [];
            unset($data['shared_user_ids']);

            if (! empty($data['owner_user_id'])) {
                $sharedUserIds[] = (int) $data['owner_user_id'];
            }

            $sharedUserIds = array_values(array_unique($sharedUserIds));

            $creditCard = $this->creditCards->create($data, $sharedUserIds);

            return (new CreditCardResource($creditCard))
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to create credit card',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $creditCard = $this->creditCards->findById((int) $id);

            if (! $creditCard) {
                return response()->json([
                    'error' => 'Credit card not found',
                ], 404);
            }

            return (new CreditCardResource($creditCard))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to retrieve credit card',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(CreditCardRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $data['is_shared'] = $request->boolean('is_shared');

            if (! empty($data['owner_user_id'])) {
                $owner = User::find((int) $data['owner_user_id']);
                $data['owner_name'] = $owner?->name;
            }

            $sharedUserIds = $data['shared_user_ids'] ?? [];
            unset($data['shared_user_ids']);

            if (! empty($data['owner_user_id'])) {
                $sharedUserIds[] = (int) $data['owner_user_id'];
            }

            $sharedUserIds = array_values(array_unique($sharedUserIds));

            $creditCard = $this->creditCards->update((int) $id, $data, $sharedUserIds);

            if (! $creditCard) {
                return response()->json([
                    'error' => 'Credit card not found',
                ], 404);
            }

            return (new CreditCardResource($creditCard))
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to update credit card',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $deleted = $this->creditCards->delete((int) $id);

            if (! $deleted) {
                return response()->json([
                    'error' => 'Credit card not found',
                ], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Failed to delete credit card',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
