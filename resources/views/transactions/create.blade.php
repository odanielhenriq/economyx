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

            <div id="transaction-success"
                class="hidden p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
                Transação criada com sucesso.
            </div>

            <div id="transaction-errors"
                class="hidden p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded"></div>


            {{-- Formulário principal (submit via API) --}}
            <form id="transaction-form" method="POST" class="space-y-6">
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
                            <input type="text" name="description" value=""
                                class="mt-1 w-full rounded border-gray-300 text-sm"
                                placeholder="Ex: Mercado, aluguel...">
                        </div>

                        {{-- Tipo (ex: Receita / Despesa) --}}
                        <div>
                            <label class="text-sm text-gray-600">Tipo</label>
                            <select name="type_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
                            </select>
                        </div>

                        {{-- Data da transação --}}
                        <div>
                            <label class="text-sm text-gray-600">Data</label>
                            <input type="date" name="transaction_date"
                                value="{{ now()->toDateString() }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    {{-- Categoria (em linha própria) --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Categoria</label>
                            <select name="category_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
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

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="installment_toggle" class="rounded border-gray-300">
                        <label for="installment_toggle" class="text-sm text-gray-600">Compra parcelada</label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Valor total da compra (R$)</label>
                            <input type="number" step="0.01" min="0" name="total_amount" id="total_amount"
                                value="" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <p class="mt-1 text-xs text-gray-500">
                                Somatório de todas as parcelas (ex: 10 x 200 = 2.000).
                            </p>
                        </div>

                        {{-- Valor da parcela (amount) --}}
                        <div id="amount-wrapper" style="display: none;">
                            <label class="text-sm text-gray-600">Valor da parcela (R$)</label>
                            <input type="number" step="0.01" min="0" name="amount" id="amount"
                                value="" class="mt-1 w-full rounded border-gray-300 text-sm">
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
                            </select>
                        </div>

                        {{-- Select de cartão (só faz sentido quando payment_method_id = 1 / Credit Card) --}}
                        <div id="credit-card-wrapper">
                            <label class="text-sm text-gray-600">Cartão</label>
                            <select name="credit_card_id" id="credit_card_id"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">Nenhum</option>
                            </select>
                        </div>
                    </div>


                </div>



                {{-- ======================================================
                    CARD 3 — PARCELAMENTO (número atual / total de parcelas)
                ======================================================= --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4" id="installment-card"
                    style="display: none;">
                    <h3 class="font-semibold text-gray-700 mb-2">Parcelamento</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Número da parcela atual (ex: 1) --}}
                        <div>
                            <label class="text-sm text-gray-600">Parcela atual</label>
                            <input type="number" min="1" name="installment_number" id="installment_number"
                                value=""
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                        {{-- Total de parcelas (ex: 10) --}}
                        <div>
                            <label class="text-sm text-gray-600">Total de parcelas</label>
                            <input type="number" min="1" name="installment_total" id="installment_total"
                                value=""
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

                    <div id="users-grid" class="grid grid-cols-2 md:grid-cols-3 gap-2"></div>

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

    {{-- Script da página: carrega dados via API, submit via fetch --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentUserId = Number(@json(auth()->id()));
            const form = document.getElementById('transaction-form');
            const errorsEl = document.getElementById('transaction-errors');
            const successEl = document.getElementById('transaction-success');
            const submitBtn = form.querySelector('button[type="submit"]');
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
            const usersGrid = document.getElementById('users-grid');
            let creditCardMethodId = null;

            const apiFetch = async (url) => {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Erro ao carregar ${url}`);
                }

                return response.json();
            };

            const fillSelect = (select, items, labelBuilder) => {
                const currentValue = select.value;
                select.querySelectorAll('option:not([value=""])').forEach((option) => option.remove());

                items.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = labelBuilder(item);
                    if (String(item.id) === String(currentValue)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            };

            const renderUsers = (users) => {
                usersGrid.innerHTML = '';

                users.forEach((user) => {
                    const label = document.createElement('label');
                    label.className = 'inline-flex items-center space-x-2';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'user_ids[]';
                    checkbox.value = user.id;
                    checkbox.className = 'rounded border-gray-300 text-indigo-600';
                    checkbox.checked = currentUserId && Number(user.id) === currentUserId;

                    const span = document.createElement('span');
                    span.className = 'text-sm';
                    span.textContent = user.name;

                    label.appendChild(checkbox);
                    label.appendChild(span);
                    usersGrid.appendChild(label);
                });
            };

            const loadOptions = async () => {
                const userQuery = currentUserId ? `?user_id=${currentUserId}` : '';

                const [types, categories, paymentMethods, creditCards, users] = await Promise.all([
                    apiFetch('/api/types'),
                    apiFetch('/api/categories'),
                    apiFetch('/api/payment-methods'),
                    apiFetch('/api/credit-cards'),
                    apiFetch(`/api/users${userQuery}`),
                ]);

                const typeList = types.data ?? [];
                const categoryList = categories.data ?? [];
                const paymentMethodList = paymentMethods.data ?? [];
                const creditCardList = creditCards.data ?? [];
                const userList = users.data ?? [];

                fillSelect(typeSelect, typeList, (item) => item.name);
                fillSelect(categorySelect, categoryList, (item) => item.name);
                fillSelect(paymentSelect, paymentMethodList, (item) => item.name);
                fillSelect(creditCardSelect, creditCardList, (item) => {
                    const ownerLabel = item.owner?.name ?? item.owner_name;
                    return ownerLabel ? `${item.name} (${ownerLabel})` : item.name;
                });
                renderUsers(userList);

                creditCardMethodId = paymentMethodList.find((item) => item.slug === 'cc')?.id ?? null;
                toggleCreditCard();
            };

            function toggleCreditCard() {
                const methodId = creditCardMethodId ?? '1';

                if (paymentSelect.value == methodId) {
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

                document.querySelectorAll('input[name="user_ids[]"]').forEach((checkbox) => {
                    if (checkbox.checked) {
                        params.append('user_ids[]', checkbox.value);
                    }
                });

                return params.toString();
            }

            function clearErrors() {
                errorsEl.classList.add('hidden');
                errorsEl.innerHTML = '';
            }

            function showErrors(errors) {
                const messages = Object.values(errors || {}).flat();

                if (!messages.length) {
                    return;
                }

                errorsEl.innerHTML = '';
                const title = document.createElement('div');
                title.className = 'font-semibold';
                title.textContent = 'Erros ao salvar:';
                errorsEl.appendChild(title);

                const list = document.createElement('ul');
                list.className = 'list-disc list-inside';
                messages.forEach((message) => {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.appendChild(item);
                });
                errorsEl.appendChild(list);
                errorsEl.classList.remove('hidden');
            }

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

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearErrors();
                successEl.classList.add('hidden');

                if (!installmentToggle.checked) {
                    amountInput.value = totalAmountInput.value;
                    installmentNumberInput.value = '';
                    installmentTotalInput.value = '';
                }

                const formData = new FormData(form);

                if (!installmentToggle.checked) {
                    formData.delete('installment_number');
                    formData.delete('installment_total');
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Salvando...';

                try {
                    const response = await fetch('/api/transactions', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                        },
                        body: formData,
                    });

                    if (response.status === 422) {
                        const data = await response.json();
                        showErrors(data.errors);
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('Erro ao salvar transação.');
                    }

                    successEl.classList.remove('hidden');
                    window.location.href = "{{ route('transactions.index') }}";
                } catch (error) {
                    showErrors({
                        general: [error.message || 'Erro inesperado ao salvar.'],
                    });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar';
                }
            });

            loadOptions().catch((error) => {
                showErrors({
                    general: [error.message || 'Erro ao carregar dados do formulário.'],
                });
            });
        });
    </script>


</x-app-layout>
