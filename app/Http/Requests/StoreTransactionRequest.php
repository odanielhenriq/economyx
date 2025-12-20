<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description'           => 'nullable|string',
            'amount'                => 'required|numeric',
            'total_amount'          => 'required|numeric',
            'transaction_date'      => 'required|date',
            'category_id'           => 'required|exists:categories,id',
            'type_id'               => 'required|exists:types,id',
            'payment_method_id'     => 'required|exists:payment_methods,id',
            'credit_card_id'        => 'nullable|exists:credit_cards,id|required_if:payment_method_id,1',
            'installment_number'    => 'nullable|integer|min:1',
            'installment_total'     => 'nullable|integer|min:1',
            'user_ids'              => 'required|array|min:1',
            'user_ids.*'            => 'exists:users,id',
            'edit_scope'            => 'nullable|in:single,template',
        ];
    }

    public function messages(): array
    {
        return [
            'description.string' => 'A descrição deve ser um texto válido.',

            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric'  => 'O valor deve ser numérico.',

            'total_amount.required' => 'O valor é obrigatório.',
            'total_amount.numeric'  => 'O valor deve ser numérico.',

            'transaction_date.required' => 'A data da transação é obrigatória.',
            'transaction_date.date'     => 'A data da transação deve estar em um formato válido.',

            'category_id.required' => 'A categoria é obrigatória.',
            'category_id.exists'   => 'A categoria selecionada não existe.',

            'type_id.required' => 'O tipo é obrigatório.',
            'type_id.exists'   => 'O tipo selecionado não existe.',

            'payment_method_id.required' => 'A forma de pagamento é obrigatória.',
            'payment_method_id.exists'   => 'A forma de pagamento selecionada não existe.',

            'installment_number.integer' => 'O número da parcela deve ser um valor inteiro.',
            'installment_number.min'     => 'O número da parcela deve ser pelo menos 1.',

            'installment_total.integer' => 'O total de parcelas deve ser um valor inteiro.',
            'installment_total.min'     => 'O total de parcelas deve ser pelo menos 1.',

            'user_ids.required' => 'Pelo menos um usuário deve ser selecionado.',
            'user_ids.array'    => 'A lista de usuários deve ser um array.',
            'user_ids.min'      => 'É necessário selecionar ao menos um usuário.',
            'user_ids.*.exists' => 'Usuário selecionado inválido.',
        ];
    }
}
