<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $users = $user->networkUsers()
            ->sortBy('name')
            ->values();

        return UserResource::collection($users);
    }
}
