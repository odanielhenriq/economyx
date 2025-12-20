<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecurringTransactionRequest;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\PaymentMethod;
use App\Models\RecurringTransaction;
use App\Models\Type;
use App\Models\User;
use App\Repositories\RecurringTransactionRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class RecurringTransactionWebController extends Controller
{
    public function __construct(
        private RecurringTransactionRepositoryInterface $recurringTransactions
    ) {}

    public function index()
    {
        return view('recurring_transactions.index');
    }

    public function create()
    {
        $formData = $this->getFormData();

        return view('recurring_transactions.create', array_merge(
            $formData,
            ['prefill' => request()->query()]
        ));
    }

    public function store(RecurringTransactionRequest $request)
    {
        $data = $request->validated();

        $userIds = $data['user_ids'];
        unset($data['user_ids']);

        $this->recurringTransactions->create($data, $userIds);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('success', 'Conta fixa criada com sucesso!');
    }

    public function edit(RecurringTransaction $recurringTransaction)
    {
        $formData = $this->getFormData();
        $recurringTransaction->load('users');

        return view('recurring_transactions.edit', array_merge(
            $formData,
            ['recurringTransaction' => $recurringTransaction]
        ));
    }

    public function update(RecurringTransactionRequest $request, RecurringTransaction $recurringTransaction)
    {
        $data = $request->validated();

        $userIds = $data['user_ids'] ?? null;
        unset($data['user_ids']);

        $this->recurringTransactions->update($recurringTransaction->id, $data, $userIds);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('success', 'Conta fixa atualizada com sucesso!');
    }

    public function destroy(RecurringTransaction $recurringTransaction)
    {
        $this->recurringTransactions->delete($recurringTransaction->id);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('success', 'Conta fixa removida com sucesso!');
    }

    private function getFormData(): array
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        return [
            'categories' => Category::orderBy('name')->get(),
            'types' => Type::orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::orderBy('name')->get(),
            'creditCards' => $viewer->creditCards()->with('owner')->orderBy('name')->get(),
            'users' => $viewer->networkUsers(),
        ];
    }
}
