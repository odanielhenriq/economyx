<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Type;
use App\Models\PaymentMethod;
use App\Models\CreditCard;
use App\Models\User;
use App\Repositories\TransactionRepositoryInterface;

class TransactionWebController extends Controller
{

    public function __construct(
        private TransactionRepositoryInterface $transactions
    ) {}

    public function index()
    {
        // Por enquanto, só carrega a view.
        // Quem vai buscar as transações é o JS via fetch().
        return view('transactions.index');
    }

    public function create()
    {
        $categories     = Category::orderBy('name')->get();
        $types          = Type::orderBy('name')->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();
        $creditCards    = CreditCard::orderBy('name')->get();
        $users          = User::orderBy('name')->get();


        return view('transactions.create', compact(
            'categories',
            'types',
            'paymentMethods',
            'creditCards',
            'users'
        ));
    }

    public function store(StoreTransactionRequest $request)
    {

        try {
            $data = $request->validated();

            $usersIds = $data['user_ids'];
            unset($data['user_ids']);

            $transaction = $this->transactions->createTransaction($data, $usersIds);
            return redirect()
                ->route('transactions.index')
                ->with('success', 'Transação criada com sucesso!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erro ao salvar transação: ' . $e->getMessage()]);
        }
    }
}
