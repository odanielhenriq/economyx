<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Type;
use App\Models\PaymentMethod;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Support\Facades\Auth;



class TransactionWebController extends Controller
{

    public function __construct(
        private TransactionRepositoryInterface $transactions
    ) {}

    public function index()
    {
        // Por enquanto, só carrega a view.
        // Quem vai buscar as transações é o JS via fetch().
        $users          = User::orderBy('name')->get();
        $categories     = Category::orderBy('name')->get();
        $types          = Type::orderBy('name')->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        return view('transactions.index', compact(
            'users',
            'categories',
            'types',
            'paymentMethods'
        ));
    }

    public function create()
    {
        /** @var \App\Models\User $viewer */
        $viewer = Auth::user();

        $categories     = Category::orderBy('name')->get();
        $types          = Type::orderBy('name')->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        // pessoas pra dividir a conta: sua "rede"
        $users          = $viewer->networkUsers();

        // cartões que ESTE usuário pode usar (dele ou emprestados)
        $creditCards    = $viewer->creditCards()
            ->with('owner') // pra exibir (Daniel, Joyce, Neusa, etc)
            ->orderBy('name')
            ->get();

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

    public function edit(Transaction $transaction)
    {
        /** @var \App\Models\User $viewer */
        $viewer = Auth::user();

        $transaction->load(['users', 'creditCard']);

        $categories     = Category::orderBy('name')->get();
        $types          = Type::orderBy('name')->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        $users          = $viewer->networkUsers();
        $creditCards    = $viewer->creditCards()
            ->with('owner')
            ->orderBy('name')
            ->get();

        return view('transactions.edit', compact(
            'transaction',
            'categories',
            'types',
            'paymentMethods',
            'creditCards',
            'users'
        ));
    }

    public function update(StoreTransactionRequest $request, Transaction $transaction)
    {
        try {
            $data = $request->validated();

            $userIds = $data['user_ids'] ?? null;
            unset($data['user_ids']);

            $this->transactions->updateTransaction(
                $transaction->id,
                $data,
                $userIds
            );

            return redirect()
                ->route('transactions.index')
                ->with('success', 'Transação atualizada com sucesso!');
        } catch (\Throwable $th) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erro ao atualizar transação: ' . $th->getMessage()]);
        }
    }

    public function destroy(Transaction $transaction)
    {
        try {
            $this->transactions->deleteTransaction($transaction->id);

            return redirect()
                ->route('transactions.index')
                ->with('success', 'Transação removida com sucesso!');
        } catch (\Throwable $th) {
            return back()
                ->withErrors(['error' => 'Erro ao remover transação: ' . $th->getMessage()]);
        }
    }

    private function getAvailableCreditCards(User $viewer)
    {
        // IDs da minha rede (eu + relacionados)
        $networkIds = $viewer->networkUsers()->pluck('id');

        return CreditCard::query()
            ->whereIn('owner_user_id', $networkIds)
            ->where(function ($q) use ($viewer) {
                // sempre posso ver meus cartões
                $q->where('owner_user_id', $viewer->id)
                    // cartões de terceiros só se forem compartilhados
                    ->orWhere('is_shared', true);
            })
            ->orderBy('name')
            ->get();
    }
}
