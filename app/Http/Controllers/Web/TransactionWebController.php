<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
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
        // Mesmo repository, mas aqui a saída é view / redirect, não JSON
        private TransactionRepositoryInterface $transactions
    ) {}

    public function index()
    {
        // Controller web bem fino:
        // só carrega os dados necessários para a tela de listagem.
        // As transações em si serão carregadas via JS (API).
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

        // Dados de apoio do formulário
        $categories     = Category::orderBy('name')->get();
        $types          = Type::orderBy('name')->get();
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        // "Rede" de pessoas com quem você divide contas
        $users          = $viewer->networkUsers();

        // Cartões que ESTE usuário pode usar:
        //  - cartões dele
        //  - cartões compartilhados por outras pessoas
        $creditCards    = $viewer->creditCards()
            ->with('owner') // pra exibir nome do dono (Daniel, Joyce, etc)
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

            // usuários relacionados (pra divisão de gastos)
            $usersIds = $data['user_ids'];
            unset($data['user_ids']);

            // Cria usando o repository (reaproveita a regra da API)
            $transaction = $this->transactions->createTransaction($data, $usersIds);

            // Depois de criado, Transaction::booted() cuida das parcelas

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

        // Já carrega usuários relacionados e cartão da transação
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

            // Atualiza via repository
            $this->transactions->updateTransaction(
                $transaction->id,
                $data,
                $userIds
            );

            // OBS: aqui você ainda não lida com recalcular parcelas ao editar

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

    /**
     * NÃO está sendo usada neste controller,
     * porque você optou por usar $viewer->creditCards().
     * Mas é uma alternativa mais "raw" que busca pelo owner_user_id e is_shared.
     */
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
