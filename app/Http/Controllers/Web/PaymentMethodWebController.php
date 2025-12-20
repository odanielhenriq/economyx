<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Repositories\PaymentMethodRepositoryInterface;

class PaymentMethodWebController extends Controller
{
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethods
    ) {}

    public function index()
    {
        return view('settings.payment-methods.index');
    }

    public function create()
    {
        return view('settings.payment-methods.create');
    }

    public function store(PaymentMethodRequest $request)
    {
        $data = $request->validated();

        $this->paymentMethods->create($data);

        return redirect()
            ->route('payment-methods.index')
            ->with('success', 'Forma de pagamento criada com sucesso!');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('settings.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $data = $request->validated();

        $this->paymentMethods->update($paymentMethod->id, $data);

        return redirect()
            ->route('payment-methods.index')
            ->with('success', 'Forma de pagamento atualizada com sucesso!');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $this->paymentMethods->delete($paymentMethod->id);

        return redirect()
            ->route('payment-methods.index')
            ->with('success', 'Forma de pagamento removida com sucesso!');
    }

}
