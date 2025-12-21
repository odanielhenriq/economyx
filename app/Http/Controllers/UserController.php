<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id');

        if ($userId) {
            $user = User::find((int) $userId);

            if (! $user) {
                return response()->json([
                    'error' => 'User not found',
                ], 404);
            }

            $users = $user->networkUsers()
                ->sortBy('name')
                ->values();
        } else {
            $users = User::query()
                ->orderBy('name')
                ->get();
        }

        return UserResource::collection($users);
    }
}
