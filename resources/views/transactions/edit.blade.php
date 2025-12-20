<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Editar transação</h2>

            <a href="{{ route('transactions.index') }}" class="text-sm text-indigo-600 hover:underline">
                Voltar para lista
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded">
                    <div class="font-semibold">Erros ao salvar:</div>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <form method="POST" action="{{ route('transactions.update', $transaction) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @if ($transaction->recurring_transaction_id)
                    <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                        <h3 class="font-semibold text-gray-700 mb-2">Atualização da recorrência</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                            <label class="flex items-start gap-2">
                                <input type="radio" name="edit_scope" value="single" class="mt-1"
                                    @checked(old('edit_scope', 'single') === 'single')>
                                <span>
                                    <strong>Alterar só este mês</strong>
                                    <span class="block text-xs text-gray-500">
                                        Mantém a conta fixa como está e quebra o vínculo desta transação.
                                    </span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="edit_scope" value="template" class="mt-1"
                                    @checked(old('edit_scope') === 'template')>
                                <span>
                                    <strong>Alterar conta fixa</strong>
                                    <span class="block text-xs text-gray-500">
                                        Atualiza o template e alinha esta transação do mês atual.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                @endif

                {{-- CARD 1 — INFORMAÇÕES GERAIS --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Informações gerais</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        {{-- Descrição --}}
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Descrição</label>
                            <input type="text" name="description"
                                value="{{ old('description', $transaction->description) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                        {{-- Tipo --}}
                        <div>
                            <label class="text-sm text-gray-600">Tipo</label>
                            <select name="type_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}"
                                        {{ old('type_id', $transaction->type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Data --}}
                        <div>
                            <label class="text-sm text-gray-600">Data</label>
                            <input type="date" name="transaction_date"
                                value="{{ old('transaction_date', optional($transaction->transaction_date)->toDateString()) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Categoria</label>
                            <select name="category_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $transaction->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- CARD 2 — PAGAMENTO --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>
                        <label class="text-sm text-gray-600">Valor da parcela (R$)</label>
                        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}"
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                        <p class="mt-1 text-xs text-gray-500">
                            Valor da parcela individual (se houver parcelamento).
                        </p>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Forma de pagamento</label>
                        <select name="payment_method_id" id="payment_method_id"
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">...</option>
                            @foreach ($paymentMethods as $pm)
                                <option value="{{ $pm->id }}"
                                    {{ old('payment_method_id') == $pm->id ? 'selected' : '' }}>
                                    {{ $pm->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="credit-card-wrapper">
                        <label class="text-sm text-gray-600">Cartão</label>
                        <select name="credit_card_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Nenhum</option>
                            @foreach ($creditCards as $card)
                                @php $ownerLabel = $card->owner?->name ?? $card->owner_name; @endphp
                                <option value="{{ $card->id }}"
                                    {{ old('credit_card_id') == $card->id ? 'selected' : '' }}>
                                    {{ $card->name }} @if ($ownerLabel)
                                        ({{ $ownerLabel }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                {{-- linha extra com valor total --}}
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-gray-600">Valor total da compra (R$)</label>
                        <input type="number" step="0.01" min="0" name="total_amount"
                            value="{{ old('total_amount') }}" class="mt-1 w-full rounded border-gray-300 text-sm">
                        <p class="mt-1 text-xs text-gray-500">
                            Somatório de todas as parcelas (ex: 10 x 200 = 2.000).
                        </p>
                    </div>
                </div>


                {{-- CARD 3 — PARCELAMENTO --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Parcelamento</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Parcela atual</label>
                            <input type="number" min="1" name="installment_number"
                                value="{{ old('installment_number', $transaction->installment_number) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600">Total de parcelas</label>
                            <input type="number" min="1" name="installment_total"
                                value="{{ old('installment_total', $transaction->installment_total) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>
                </div>

                {{-- CARD 4 — PARTICIPANTES --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-3">
                    <h3 class="font-semibold text-gray-700">Participantes</h3>

                    @php
                        $selectedUsers = old('user_ids', $transaction->users->pluck('id')->toArray());
                    @endphp

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach ($users as $user)
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                    class="rounded border-gray-300 text-indigo-600"
                                    {{ in_array($user->id, $selectedUsers) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $user->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- BOTÕES --}}
                @php
                    $recurringQuery = [
                        'description' => old('description', $transaction->description),
                        'amount' => old('amount', $transaction->amount),
                        'total_amount' => old('total_amount', $transaction->total_amount),
                        'category_id' => old('category_id', $transaction->category_id),
                        'type_id' => old('type_id', $transaction->type_id),
                        'payment_method_id' => old('payment_method_id', $transaction->payment_method_id),
                        'credit_card_id' => old('credit_card_id', $transaction->credit_card_id),
                        'transaction_date' => old('transaction_date', optional($transaction->transaction_date)->toDateString()),
                        'user_ids' => old('user_ids', $transaction->users->pluck('id')->toArray()),
                    ];
                @endphp
                <div class="flex justify-between gap-3 items-center">
                    <a href="{{ route('recurring-transactions.create', $recurringQuery) }}"
                        class="text-sm text-indigo-600 hover:underline">
                        Criar template de conta fixa
                    </a>

                    <div class="flex gap-3">
                        <a href="{{ route('transactions.index') }}"
                            class="px-4 py-2 text-sm border rounded text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </a>

                        <button type="submit"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            Salvar alterações
                        </button>
                    </div>
                </div>

            </form>
            <form method="POST" action="{{ route('transactions.destroy', $transaction) }}"
                onsubmit="return confirm('Tem certeza que deseja remover esta transação?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 text-sm border border-red-500 text-red-600 rounded hover:bg-red-50">
                    Excluir
                </button>
            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentSelect = document.getElementById('payment_method_id');
            const creditCardWrapper = document.getElementById('credit-card-wrapper');

            function toggleCreditCard() {
                if (paymentSelect.value == '1') { // 1 = Credit Card
                    creditCardWrapper.style.display = 'block';
                } else {
                    creditCardWrapper.style.display = 'none';
                }
            }

            toggleCreditCard();
            paymentSelect.addEventListener('change', toggleCreditCard);
        });
    </script>

</x-app-layout>
