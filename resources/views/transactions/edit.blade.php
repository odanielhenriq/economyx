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

            <div id="transaction-success"
                class="hidden p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
                Transação atualizada com sucesso.
            </div>

            <div id="transaction-errors"
                class="hidden p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded"></div>


            <form id="transaction-form" method="POST" class="space-y-6">
                @csrf

                <div id="edit-scope-card" class="p-6 bg-white rounded shadow-sm border space-y-4"
                    style="display: none;">
                    <h3 class="font-semibold text-gray-700 mb-2">Atualização da recorrência</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                        <label class="flex items-start gap-2">
                            <input type="radio" name="edit_scope" value="single" class="mt-1" checked>
                            <span>
                                <strong>Alterar só este mês</strong>
                                <span class="block text-xs text-gray-500">
                                    Mantém a conta fixa como está e quebra o vínculo desta transação.
                                </span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2">
                            <input type="radio" name="edit_scope" value="template" class="mt-1">
                            <span>
                                <strong>Alterar conta fixa</strong>
                                <span class="block text-xs text-gray-500">
                                    Atualiza o template e alinha esta transação do mês atual.
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- CARD 1 — INFORMAÇÕES GERAIS --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Informações gerais</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        {{-- Descrição --}}
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Descrição</label>
                            <input type="text" name="description" value=""
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                        {{-- Tipo --}}
                        <div>
                            <label class="text-sm text-gray-600">Tipo</label>
                            <select name="type_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
                            </select>
                        </div>

                        {{-- Data --}}
                        <div>
                            <label class="text-sm text-gray-600">Data</label>
                            <input type="date" name="transaction_date"
                                value=""
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <label class="text-sm text-gray-600">Categoria</label>
                            <select name="category_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">...</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- CARD 2 — PAGAMENTO --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>
                        <label class="text-sm text-gray-600">Valor da parcela (R$)</label>
                        <input type="number" step="0.01" min="0" name="amount" value=""
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
                        </select>
                    </div>

                    <div id="credit-card-wrapper">
                        <label class="text-sm text-gray-600">Cartão</label>
                        <select name="credit_card_id" id="credit_card_id"
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Nenhum</option>
                        </select>
                    </div>

                </div>

                {{-- linha extra com valor total --}}
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-gray-600">Valor total da compra (R$)</label>
                        <input type="number" step="0.01" min="0" name="total_amount"
                            value="" class="mt-1 w-full rounded border-gray-300 text-sm">
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
                                value=""
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600">Total de parcelas</label>
                            <input type="number" min="1" name="installment_total"
                                value=""
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>
                </div>

                {{-- CARD 4 — PARTICIPANTES --}}
                <div class="p-6 bg-white rounded shadow-sm border space-y-3">
                    <h3 class="font-semibold text-gray-700">Participantes</h3>

                    <div id="users-grid" class="grid grid-cols-2 md:grid-cols-3 gap-2"></div>
                </div>

                {{-- BOTÕES --}}
                <div class="flex justify-between gap-3 items-center">
                    <a href="{{ route('recurring-transactions.create') }}" id="create-recurring-template"
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
            <button id="delete-transaction"
                class="px-4 py-2 text-sm border border-red-500 text-red-600 rounded hover:bg-red-50">
                Excluir
            </button>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transactionId = Number(@json($transaction->id));
            const currentUserId = Number(@json(auth()->id()));
            const form = document.getElementById('transaction-form');
            const errorsEl = document.getElementById('transaction-errors');
            const successEl = document.getElementById('transaction-success');
            const submitBtn = form.querySelector('button[type="submit"]');
            const deleteBtn = document.getElementById('delete-transaction');
            const editScopeCard = document.getElementById('edit-scope-card');
            const editScopeInputs = editScopeCard.querySelectorAll('input[name="edit_scope"]');

            const paymentSelect = document.getElementById('payment_method_id');
            const creditCardWrapper = document.getElementById('credit-card-wrapper');
            const creditCardSelect = document.getElementById('credit_card_id');
            const typeSelect = document.querySelector('select[name="type_id"]');
            const categorySelect = document.querySelector('select[name="category_id"]');
            const usersGrid = document.getElementById('users-grid');
            const recurringLink = document.getElementById('create-recurring-template');
            const descriptionInput = document.querySelector('input[name="description"]');
            const amountInput = document.querySelector('input[name="amount"]');
            const totalAmountInput = document.querySelector('input[name="total_amount"]');
            const dateInput = document.querySelector('input[name="transaction_date"]');
            const installmentNumberInput = document.querySelector('input[name="installment_number"]');
            const installmentTotalInput = document.querySelector('input[name="installment_total"]');

            let creditCardMethodId = null;
            let transactionData = null;
            let userListCache = [];

            const apiFetch = async (url, options = {}) => {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        ...(options.headers || {}),
                    },
                    ...options,
                });

                if (!response.ok) {
                    throw new Error(`Erro ao carregar ${url}`);
                }

                return response.json();
            };

            const fillSelect = (select, items, labelBuilder) => {
                select.querySelectorAll('option:not([value=""])').forEach((option) => option.remove());

                items.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = labelBuilder(item);
                    select.appendChild(option);
                });
            };

            const renderUsers = (users, selectedIds = []) => {
                usersGrid.innerHTML = '';

                users.forEach((user) => {
                    const label = document.createElement('label');
                    label.className = 'inline-flex items-center space-x-2';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'user_ids[]';
                    checkbox.value = user.id;
                    checkbox.className = 'rounded border-gray-300 text-indigo-600';

                    const shouldCheck = selectedIds.includes(Number(user.id))
                        || (selectedIds.length === 0 && currentUserId && Number(user.id) === currentUserId);
                    checkbox.checked = shouldCheck;

                    const span = document.createElement('span');
                    span.className = 'text-sm';
                    span.textContent = user.name;

                    label.appendChild(checkbox);
                    label.appendChild(span);
                    usersGrid.appendChild(label);
                });
            };

            const toggleCreditCard = () => {
                const methodId = creditCardMethodId ?? '1';

                if (paymentSelect.value == methodId) {
                    creditCardWrapper.style.display = 'block';
                } else {
                    creditCardWrapper.style.display = 'none';
                }
            };

            const buildRecurringQuery = () => {
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
            };

            const clearErrors = () => {
                errorsEl.classList.add('hidden');
                errorsEl.innerHTML = '';
            };

            const showErrors = (errors) => {
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

                creditCardMethodId = paymentMethodList.find((item) => item.slug === 'cc')?.id ?? null;

                userListCache = userList;
                renderUsers(userListCache, []);
            };

            const loadTransaction = async () => {
                const data = await apiFetch(`/api/transactions/${transactionId}`);
                transactionData = data.data ?? null;

                if (!transactionData) {
                    throw new Error('Transação não encontrada.');
                }
            };

            const applyTransaction = () => {
                if (!transactionData) {
                    return;
                }

                descriptionInput.value = transactionData.description ?? '';
                amountInput.value = transactionData.amount ?? '';
                totalAmountInput.value = transactionData.total_amount ?? '';
                dateInput.value = transactionData.transaction_date ?? '';
                installmentNumberInput.value = transactionData.installments?.number ?? '';
                installmentTotalInput.value = transactionData.installments?.total ?? '';

                typeSelect.value = transactionData.type_id ?? '';
                categorySelect.value = transactionData.category_id ?? '';
                paymentSelect.value = transactionData.payment_method_id ?? '';
                creditCardSelect.value = transactionData.credit_card_id ?? '';

                if (transactionData.recurring_transaction_id) {
                    editScopeCard.style.display = 'block';
                    editScopeInputs.forEach((input) => {
                        input.disabled = false;
                    });
                } else {
                    editScopeCard.style.display = 'none';
                    editScopeInputs.forEach((input) => {
                        input.disabled = true;
                    });
                }

                if (userListCache.length) {
                    const selectedIds = (transactionData.users ?? []).map((user) => Number(user.id));
                    renderUsers(userListCache, selectedIds);
                }

                toggleCreditCard();
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearErrors();
                successEl.classList.add('hidden');

                const formData = new FormData(form);

                submitBtn.disabled = true;
                submitBtn.textContent = 'Salvando...';

                try {
                    const response = await fetch(`/api/transactions/${transactionId}`, {
                        method: 'PUT',
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
                        throw new Error('Erro ao atualizar transação.');
                    }

                    successEl.classList.remove('hidden');
                    window.location.href = "{{ route('transactions.index') }}";
                } catch (error) {
                    showErrors({
                        general: [error.message || 'Erro inesperado ao salvar.'],
                    });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar alterações';
                }
            });

            deleteBtn.addEventListener('click', async () => {
                if (!confirm('Tem certeza que deseja remover esta transação?')) {
                    return;
                }

                deleteBtn.disabled = true;
                deleteBtn.textContent = 'Excluindo...';

                try {
                    const response = await fetch(`/api/transactions/${transactionId}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Erro ao excluir transação.');
                    }

                    window.location.href = "{{ route('transactions.index') }}";
                } catch (error) {
                    showErrors({
                        general: [error.message || 'Erro inesperado ao excluir.'],
                    });
                    deleteBtn.disabled = false;
                    deleteBtn.textContent = 'Excluir';
                }
            });

            recurringLink.addEventListener('click', (event) => {
                const query = buildRecurringQuery();
                recurringLink.href = query ? `${recurringLink.href.split('?')[0]}?${query}` : recurringLink.href;
            });

            Promise.all([loadTransaction(), loadOptions()])
                .then(applyTransaction)
                .catch((error) => {
                    showErrors({
                        general: [error.message || 'Erro ao carregar dados da transação.'],
                    });
                });

            paymentSelect.addEventListener('change', toggleCreditCard);
        });
    </script>

</x-app-layout>
