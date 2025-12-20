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

class RecurringTemplateWebController extends Controller
{
    public function __construct(
        private RecurringTransactionRepositoryInterface $recurringTransactions
    ) {}

    public function index()
    {
        return view('settings.recurring-templates.index');
    }

    public function create()
    {
        $formData = $this->getFormData();

        return view('settings.recurring-templates.create', $formData);
    }

    public function store(RecurringTransactionRequest $request)
    {
        $data = $request->validated();

        $userIds = $data['user_ids'];
        unset($data['user_ids']);

        $this->recurringTransactions->create($data, $userIds);

        return redirect()
            ->route('recurring-templates.index')
            ->with('success', 'Template recorrente criado com sucesso!');
    }

    public function edit(RecurringTransaction $recurringTemplate)
    {
        $formData = $this->getFormData();
        $recurringTemplate->load('users');

        return view('settings.recurring-templates.edit', array_merge(
            $formData,
            ['recurringTemplate' => $recurringTemplate]
        ));
    }

    public function update(RecurringTransactionRequest $request, RecurringTransaction $recurringTemplate)
    {
        $data = $request->validated();

        $userIds = $data['user_ids'] ?? null;
        unset($data['user_ids']);

        $this->recurringTransactions->update($recurringTemplate->id, $data, $userIds);

        return redirect()
            ->route('recurring-templates.index')
            ->with('success', 'Template recorrente atualizado com sucesso!');
    }

    public function destroy(RecurringTransaction $recurringTemplate)
    {
        $this->recurringTransactions->delete($recurringTemplate->id);

        return redirect()
            ->route('recurring-templates.index')
            ->with('success', 'Template recorrente removido com sucesso!');
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
