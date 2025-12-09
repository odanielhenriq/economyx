<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Nova transação</h2>

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


            <form method="POST" action="{{ route('transactions.store') }}" class="space-y-6">
                @csrf

                {{-- ======================================================
                    CARD 1 — INFORMAÇÕES GERAIS (4 inputs em 1 linha)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Informações gerais</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        {{-- Descrição --}}
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Descrição</label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm"
                                placeholder="Ex: Mercado, aluguel...">
                        </div>

                        {{-- Tipo --}}
                        <div>
                            <label class="text-sm text-gray-600">Tipo</label>
                            <select name="type_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}"
                                        {{ old('type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Data --}}
                        <div>
                            <label class="text-sm text-gray-600">Data</label>
                            <input type="date" name="transaction_date"
                                value="{{ old('transaction_date', now()->toDateString()) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    {{-- Linha 2: Categoria ocupando linha separada (opcional pode ir junto) --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Categoria</label>
                            <select name="category_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>



                {{-- ======================================================
                    CARD 2 — PAGAMENTO (3 inputs em 1 linha)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Pagamento</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div>
                            <label class="text-sm text-gray-600">Valor (R$)</label>
                            <input type="number" step="0.01" min="0" name="amount"
                                value="{{ old('amount') }}" class="mt-1 w-full rounded border-gray-300 text-sm">
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
                            <select name="card_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">Nenhum</option>
                                @foreach ($creditCards as $card)
                                    @php
                                        $ownerLabel = $card->owner?->name ?? $card->owner_name;
                                    @endphp
                                    <option value="{{ $card->id }}"
                                        {{ old('card_id') == $card->id ? 'selected' : '' }}>
                                        {{ $card->name }}
                                        @if ($ownerLabel)
                                            ({{ $ownerLabel }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                        </div>

                    </div>

                </div>



                {{-- ======================================================
                    CARD 3 — PARCELAMENTO (2 inputs lado a lado)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Parcelamento</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="text-sm text-gray-600">Parcela atual</label>
                            <input type="number" min="1" name="installment_number"
                                value="{{ old('installment_number') }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600">Total de parcelas</label>
                            <input type="number" min="1" name="installment_total"
                                value="{{ old('installment_total') }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                    </div>

                </div>



                {{-- ======================================================
                    CARD 4 — PARTICIPANTES (compacto)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-3">
                    <h3 class="font-semibold text-gray-700">Participantes</h3>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach ($users as $user)
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                    class="rounded border-gray-300 text-indigo-600"
                                    {{ in_array($user->id, old('user_ids', [])) ? 'checked' : '' }}>
                                <span class="text-sm">{{ $user->name }}</span>
                            </label>
                        @endforeach
                    </div>

                </div>


                {{-- BOTÕES --}}
                <div class="flex justify-end gap-3">
                    <a href="{{ route('transactions.index') }}"
                        class="px-4 py-2 text-sm border rounded text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>

                    <button type="submit"
                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Salvar
                    </button>
                </div>

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

            // Inicializa ao carregar a página
            toggleCreditCard();

            // Atualiza quando o select mudar
            paymentSelect.addEventListener('change', toggleCreditCard);
        });
    </script>


</x-app-layout>
