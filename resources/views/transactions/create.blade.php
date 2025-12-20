<x-app-layout>

    {{-- Cabeçalho da página de criação --}}
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Nova transação</h2>

            {{-- Link pra voltar pra listagem web --}}
            <a href="{{ route('transactions.index') }}" class="text-sm text-indigo-600 hover:underline">
                Voltar para lista
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">

            {{-- Mensagem de sucesso caso venha da sessão --}}
            @if (session('success'))
                <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Erros de validação (StoreTransactionRequest) --}}
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


            {{-- Formulário principal, enviando para a rota web .store --}}
            <form method="POST" action="{{ route('transactions.store') }}" class="space-y-6">
                @csrf

                {{-- ======================================================
                    CARD 1 — INFORMAÇÕES GERAIS
                    (Descrição, Tipo, Data, Categoria)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Informações gerais</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        {{-- Descrição da transação --}}
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Descrição</label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm"
                                placeholder="Ex: Mercado, aluguel...">
                        </div>

                        {{-- Tipo (ex: Receita / Despesa) --}}
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

                        {{-- Data da transação --}}
                        <div>
                            <label class="text-sm text-gray-600">Data</label>
                            <input type="date" name="transaction_date"
                                value="{{ old('transaction_date', now()->toDateString()) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    {{-- Categoria (em linha própria) --}}
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
                    CARD 2 — PAGAMENTO
                    (Valor, Forma de pagamento, Cartão e Valor total)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Pagamento</h3>

                    @php $isInstallmentOld = (int) old('installment_total', 1) > 1; @endphp

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="installment_toggle" class="rounded border-gray-300"
                            @checked($isInstallmentOld)>
                        <label for="installment_toggle" class="text-sm text-gray-600">Compra parcelada</label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Valor total da compra (R$)</label>
                            <input type="number" step="0.01" min="0" name="total_amount" id="total_amount"
                                value="{{ old('total_amount') }}" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <p class="mt-1 text-xs text-gray-500">
                                Somatório de todas as parcelas (ex: 10 x 200 = 2.000).
                            </p>
                        </div>

                        {{-- Valor da parcela (amount) --}}
                        <div id="amount-wrapper" style="{{ $isInstallmentOld ? '' : 'display: none;' }}">
                            <label class="text-sm text-gray-600">Valor da parcela (R$)</label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount"
                                value="{{ old('amount') }}" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <p class="mt-1 text-xs text-gray-500">
                                Valor da parcela individual (se houver parcelamento).
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Forma de pagamento (vai controlar exibição do select de cartão) --}}
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

                        {{-- Select de cartão (só faz sentido quando payment_method_id = 1 / Credit Card) --}}
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


                </div>



                {{-- ======================================================
                    CARD 3 — PARCELAMENTO (número atual / total de parcelas)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4" id="installment-card"
                    style="{{ $isInstallmentOld ? '' : 'display: none;' }}">
                    <h3 class="font-semibold text-gray-700 mb-2">Parcelamento</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Número da parcela atual (ex: 1) --}}
                        <div>
                            <label class="text-sm text-gray-600">Parcela atual</label>
                            <input type="number" min="1" name="installment_number" id="installment_number"
                                value="{{ old('installment_number') }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                        {{-- Total de parcelas (ex: 10) --}}
                        <div>
                            <label class="text-sm text-gray-600">Total de parcelas</label>
                            <input type="number" min="1" name="installment_total" id="installment_total"
                                value="{{ old('installment_total') }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                    </div>

                </div>

                {{-- ======================================================
                    CARD 4 — PARTICIPANTES
                    (pessoas que vão dividir esta transação)
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


                {{-- BOTÕES DE AÇÃO DO FORM --}}
                <div class="flex justify-between items-center gap-3">
                    <a href="{{ route('recurring-transactions.create') }}"
                        id="create-recurring-template"
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
                            Salvar
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

    {{-- Script simples para mostrar/esconder o select de cartão baseado na forma de pagamento --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentSelect = document.getElementById('payment_method_id');
            const creditCardWrapper = document.getElementById('credit-card-wrapper');
            const installmentToggle = document.getElementById('installment_toggle');
            const amountWrapper = document.getElementById('amount-wrapper');
            const amountInput = document.getElementById('amount');
            const totalAmountInput = document.getElementById('total_amount');
            const installmentCard = document.getElementById('installment-card');
            const installmentNumberInput = document.getElementById('installment_number');
            const installmentTotalInput = document.getElementById('installment_total');
            const recurringLink = document.getElementById('create-recurring-template');
            const descriptionInput = document.querySelector('input[name="description"]');
            const typeSelect = document.querySelector('select[name="type_id"]');
            const categorySelect = document.querySelector('select[name="category_id"]');
            const creditCardSelect = document.querySelector('select[name="credit_card_id"]');
            const dateInput = document.querySelector('input[name="transaction_date"]');
            const userCheckboxes = document.querySelectorAll('input[name="user_ids[]"]');
            function toggleCreditCard() {
                // 1 = Credit Card (você definiu esse ID nos seeds/tabela de payment_methods)
                if (paymentSelect.value == '1') {
                    creditCardWrapper.style.display = 'block';
                } else {
                    creditCardWrapper.style.display = 'none';
                }
            }

            function syncAmountWithTotal() {
                if (!installmentToggle.checked) {
                    amountInput.value = totalAmountInput.value;
                }
            }

            function toggleInstallmentFields() {
                const isInstallment = installmentToggle.checked;
                amountWrapper.style.display = isInstallment ? 'block' : 'none';
                installmentCard.style.display = isInstallment ? 'block' : 'none';
                installmentNumberInput.disabled = !isInstallment;
                installmentTotalInput.disabled = !isInstallment;

                if (!isInstallment) {
                    installmentNumberInput.value = '';
                    installmentTotalInput.value = '';
                    syncAmountWithTotal();
                }
            }

            function buildRecurringQuery() {
                const params = new URLSearchParams();
                const descriptionValue = descriptionInput?.value?.trim();
                const totalAmountValue = totalAmountInput?.value;
                const amountValue = amountInput?.value || totalAmountValue;

                if (descriptionValue) params.set('description', descriptionValue);
                if (amountValue) params.set('amount', amountValue);
                if (totalAmountValue) params.set('total_amount', totalAmountValue);
                if (categorySelect?.value) params.set('category_id', categorySelect.value);
                if (typeSelect?.value) params.set('type_id', typeSelect.value);
                if (paymentSelect?.value) params.set('payment_method_id', paymentSelect.value);
                if (creditCardSelect?.value) params.set('credit_card_id', creditCardSelect.value);
                if (dateInput?.value) params.set('transaction_date', dateInput.value);

                userCheckboxes.forEach((checkbox) => {
                    if (checkbox.checked) {
                        params.append('user_ids[]', checkbox.value);
                    }
                });

                return params.toString();
            }

            // Inicializa ao carregar a página (útil quando veio erro de validação e old() preenche o select)
            toggleCreditCard();
            toggleInstallmentFields();

            // Atualiza sempre que o select mudar
            paymentSelect.addEventListener('change', toggleCreditCard);
            installmentToggle.addEventListener('change', toggleInstallmentFields);
            totalAmountInput.addEventListener('input', syncAmountWithTotal);
            recurringLink.addEventListener('click', (event) => {
                const query = buildRecurringQuery();
                recurringLink.href = query ? `${recurringLink.href.split('?')[0]}?${query}` : recurringLink.href;
            });
        });
    </script>


</x-app-layout>
