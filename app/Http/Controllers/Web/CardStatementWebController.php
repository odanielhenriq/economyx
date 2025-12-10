<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use Illuminate\Http\Request;

class CardStatementWebController extends Controller
{
    public function index()
    {
        $cards = CreditCard::with('owner')->get();

        return view('cards.statement', compact('cards'));
    }
}
