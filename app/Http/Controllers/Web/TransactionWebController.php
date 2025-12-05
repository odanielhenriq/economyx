<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionWebController extends Controller
{
    public function index()
    {
        // Por enquanto, só carrega a view.
        // Quem vai buscar as transações é o JS via fetch().
        return view('transactions.index');
    }
}
