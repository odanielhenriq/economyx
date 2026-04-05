<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">Nova transação</h1>
            <a href="{{ route('transactions.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
                ← Voltar
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6">

        <div id="transaction-success"
            class="hidden px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
            Transação criada com sucesso.
        </div>

        <div id="transaction-errors"
            class="hidden px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl"></div>

        <form id="transaction-form" method="POST" class="space-y-6">
            @csrf

            {{-- Card 1 — Informações gerais --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Informações gerais</h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                        <input type="text" name="description" value=""
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Ex: Mercado, aluguel...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                        <select name="type_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
                        <input type="date" name="transaction_date"
                            value="{{ now()->toDateString() }}"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Categoria</label>
                        <select name="category_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">...</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Card 2 — Pagamento --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Pagamento</h3>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="installment_toggle"
                        class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700">Compra parcelada</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div x-data="{ raw: '', onInput(e) { const d = e.target.value.replace(/\D/g,''); const n = (parseInt(d||'0')/100); this.raw = n.toFixed(2); e.target.value = 'R$ ' + n.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); } }">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Valor total da compra (R$)</label>
                        <input type="text" inputmode="numeric"
                            x-on:input="onInput($event)"
                            placeholder="R$ 0,00"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <input type="hidden" name="total_amount" id="total_amount" x-bind:value="raw">
                        <p class="mt-1 text-xs text-slate-400">Somatório de todas as parcelas (ex: 10 x 200 = 2.000).</p>
                    </div>

                    <div id="amount-wrapper" style="display: none;"
                         x-data="{ raw: '', onInput(e) { const d = e.target.value.replace(/\D/g,''); const n = (parseInt(d||'0')/100); this.raw = n.toFixed(2); e.target.value = 'R$ ' + n.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); } }">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Valor da parcela (R$)</label>
                        <input type="text" inputmode="numeric"
                            x-on:input="onInput($event)"
                            placeholder="R$ 0,00"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <input type="hidden" name="amount" id="amount" x-bind:value="raw">
                        <p class="mt-1 text-xs text-slate-400">Valor da parcela individual.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Forma de pagamento</label>
                        <select name="payment_method_id" id="payment_method_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">...</option>
                        </select>
                    </div>
                    <div id="credit-card-wrapper">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cartão</label>
                        <select name="credit_card_id" id="credit_card_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Nenhum</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Card 3 — Parcelamento --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4" id="installment-card"
                style="display: none;">
                <h3 class="text-sm font-semibold text-slate-700">Parcelamento</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Parcela atual</label>
                        <input type="number" min="1" name="installment_number" id="installment_number" value=""
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Total de parcelas</label>
                        <input type="number" min="1" name="installment_total" id="installment_total" value=""
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            {{-- Card 4 — Participantes --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                <h3 class="text-sm font-semibold text-slate-700">Participantes</h3>
                <div id="users-grid" class="grid grid-cols-2 md:grid-cols-3 gap-2"></div>
            </div>

            {{-- Ações --}}
            <div class="flex justify-between items-center gap-3">
                <a href="{{ route('recurring-transactions.create') }}"
                    id="create-recurring-template"
                    class="text-sm font-medium text-green-700 hover:text-green-900">
                    + Criar template de conta fixa
                </a>

                <div class="flex gap-3">
                    <a href="{{ route('transactions.index') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Salvar
                    </button>
                </div>
            </div>

        </form>

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
                    checkbox.className = 'rounded border-slate-300 text-green-600 focus:ring-green-500';
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
                    // Garante que o campo seja obrigatório quando é cartão
                    creditCardSelect.required = true;
                } else {
                    creditCardWrapper.style.display = 'none';
                    // Limpa o valor quando não é cartão de crédito
                    creditCardSelect.value = '';
                    creditCardSelect.required = false;
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

                // Validação: se método de pagamento é cartão de crédito, cartão é obrigatório
                const methodId = creditCardMethodId ?? '1';
                if (paymentSelect.value == methodId && !creditCardSelect.value) {
                    showErrors({
                        credit_card_id: ['É necessário selecionar um cartão quando a forma de pagamento é Cartão de Crédito.']
                    });
                    return;
                }

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

                // Se não é cartão de crédito, remove o credit_card_id do formData
                if (paymentSelect.value != methodId) {
                    formData.delete('credit_card_id');
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
