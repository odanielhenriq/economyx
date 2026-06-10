<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    public function store(User $user): RedirectResponse
    {
        $this->authorize('impersonate', $user);

        if (session()->has('impersonator_id')) {
            abort(403, 'Já existe uma sessão de impersonação ativa.');
        }

        $impersonatorId = auth()->id();

        session(['impersonator_id' => $impersonatorId]);

        Auth::login($user);

        Log::info('Impersonação iniciada', [
            'impersonator_id' => $impersonatorId,
            'target_user_id' => $user->id,
        ]);

        return redirect()->route('dashboard');
    }

    public function destroy(): RedirectResponse
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            abort(403);
        }

        $impersonator = User::query()->findOrFail($impersonatorId);
        $targetUserId = auth()->id();

        Auth::login($impersonator);
        session()->forget('impersonator_id');

        Log::info('Impersonação encerrada', [
            'impersonator_id' => $impersonatorId,
            'target_user_id' => $targetUserId,
        ]);

        return redirect()->route('admin.users.index');
    }
}
