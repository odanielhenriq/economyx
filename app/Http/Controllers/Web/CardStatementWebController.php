<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;

class CardStatementWebController extends Controller
{
    /**
     * Exibe a tela de extrato de cartão com todos os cartões disponíveis.
     * 
     * Lista TODOS os cartões do sistema, não apenas os do usuário logado,
     * para que o usuário possa visualizar faturas de qualquer cartão.
     */
    public function index()
    {
        // Busca todos os cartões com seus donos
        $cards = CreditCard::with('owner')
            ->orderBy('name')
            ->get();

        return view('cards.statement', compact('cards'));
    }
}
