<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreditCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $networkIds = $this->getNetworkUserIds();

        return [
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'closing_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'limit' => ['nullable', 'numeric', 'min:0'],
            'owner_user_id' => ['nullable', Rule::in($networkIds), 'required_without:owner_name'],
            'owner_name' => ['nullable', 'string', 'max:255', 'required_without:owner_user_id'],
            'is_shared' => ['nullable', 'boolean'],
            'shared_user_ids' => ['nullable', 'array'],
            'shared_user_ids.*' => ['integer', Rule::in($networkIds)],
        ];
    }

    private function getNetworkUserIds(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return $user->networkUsers()->pluck('id')->all();
    }
}
