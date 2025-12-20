<?php

namespace App\Http\Requests;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paymentMethod = $this->route('payment_method');
        $paymentMethodId = $paymentMethod instanceof PaymentMethod ? $paymentMethod->id : (int) $this->route('id');

        $slugRule = Rule::unique('payment_methods', 'slug');
        if ($paymentMethodId) {
            $slugRule = $slugRule->ignore($paymentMethodId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', $slugRule],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug') ?: $this->input('name');

        if ($slug !== null) {
            $this->merge(['slug' => Str::slug($slug)]);
        }
    }
}
