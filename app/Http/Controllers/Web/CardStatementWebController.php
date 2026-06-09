<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Support\NetworkScope;

class CardStatementWebController extends Controller
{
    /**
     * Exibe a tela de extrato com cartões da rede do usuário (próprios + compartilhados).
     */
    public function index()
    {
        $cards = CreditCard::with('owner')
            ->where(function ($q) {
                $user = auth()->user();
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('users', fn ($sub) => $sub->where('users.id', $user->id));
            })
            ->orderBy('name')
            ->get();

        return view('cards.statement', compact('cards'));
    }
}
