<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CardStatementWebController extends Controller
{
    public function index()
    {
        $cards = Auth::user()->creditCards()->with('owner')->get();

        return view('cards.statement', compact('cards'));
    }
}
