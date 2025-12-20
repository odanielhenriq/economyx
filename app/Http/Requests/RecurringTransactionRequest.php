<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],
            'frequency' => ['required', 'in:monthly,yearly'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date', 'required_if:frequency,yearly'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'category_id' => ['required', 'exists:categories,id'],
            'type_id' => ['required', 'exists:types,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'credit_card_id' => ['nullable', 'exists:credit_cards,id', 'required_if:payment_method_id,1'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
        ];
    }
}
