<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 data-page-title class="text-lg font-semibold text-slate-900">Editar transação</h1>
            <a href="{{ route('transactions.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
                ← Voltar para lista
            </a>
        </div>
    </x-slot>

    <div id="edit-wrapper"
         x-data="{
             ready: false,
             type_id: '',
             idDespesa: null,
             idReceita: null,
             installment_total: 0
         }"
         class="space-y-4">

        {{-- Mensagens de feedback --}}
        <div id="transaction-success"
             class="hidden px-4 py-3 text-sm text-green-800 bg-green-50 border border-green-200 rounded-xl">
            Transação atualizada com sucesso.
        </div>
        <div id="transaction-errors"
             class="hidden px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl"></div>

        {{-- ===== SKELETON ===== --}}
        <div x-show="!ready" class="space-y-4">
            {{-- Skeleton card 1 --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <div class="space-y-4">
                    <div class="h-4 w-40 bg-slate-200 rounded animate-pulse"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="col-span-3 h-10 bg-slate-200 rounded-lg animate-pulse"></div>
                        <div class="h-10 bg-slate-200 rounded-lg animate-pulse"></div>
                    </div>
                    <div class="h-12 bg-slate-200 rounded-lg animate-pulse"></div>
                    <div class="h-10 bg-slate-200 rounded-lg animate-pulse"></div>
                </div>
            </div>
            {{-- Skeleton card 2 --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <div class="space-y-4">
                    <div class="h-4 w-28 bg-slate-200 rounded animate-pulse"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="h-10 bg-slate-200 rounded-lg animate-pulse"></div>
                        <div class="h-10 bg-slate-200 rounded-lg animate-pulse"></div>
                        <div class="h-10 bg-slate-200 rounded-lg animate-pulse"></div>
                    </div>
                </div>
            </div>
            {{-- Skeleton card 3 --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <div class="space-y-3">
                    <div class="h-4 w-44 bg-slate-200 rounded animate-pulse"></div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="h-8 bg-slate-200 rounded-lg animate-pulse"></div>
                        <div class="h-8 bg-slate-200 rounded-lg animate-pulse"></div>
                        <div class="h-8 bg-slate-200 rounded-lg animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FORMULÁRIO REAL ===== --}}
        <div x-show="ready" x-transition style="display:none" class="space-y-4">

            <form id="transaction-form" method="POST" class="space-y-4">
                @csrf

                {{-- type_id gerenciado pelo Alpine (botões visuais) --}}
                <input type="hidden" name="type_id" :value="type_id">

                {{-- CARD DE ESCOPO (conta fixa) — mostrado via JS --}}
                <div id="edit-scope-card"
                     class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4"
                     style="display: none;">
                    <h3 class="text-sm font-semibold text-slate-700">Esta é uma conta fixa</h3>
                    <p class="text-sm text-slate-500">O que você quer alterar?</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="edit_scope" value="single"
                                   class="mt-1 border-slate-300 text-green-600 focus:ring-green-500" checked>
                            <span>
                                <strong class="text-sm font-semibold text-slate-800">Só este mês</strong>
                                <span class="block text-xs text-slate-500 mt-0.5">
                                    Apenas o lançamento deste mês será alterado.
                                </span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="radio" name="edit_scope" value="template"
                                   class="mt-1 border-slate-300 text-green-600 focus:ring-green-500">
                            <span>
                                <strong class="text-sm font-semibold text-slate-800">Todos os meses seguintes</strong>
                                <span class="block text-xs text-slate-500 mt-0.5">
                                    O novo valor vai valer para todos os meses futuros.
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- CARD 1 — INFORMAÇÕES GERAIS --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">Informações gerais</h3>

                    {{-- Descrição + Data --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="col-span-1 sm:col-span-1 lg:col-span-3">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                            <input type="text" name="description" value=""
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
                            <input type="date" name="transaction_date" value=""
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>

                    {{-- É entrada ou saída? (botões visuais) --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">É uma entrada ou saída?</label>
                        <div class="flex gap-3">
                            <button type="button"
                                @click="type_id = String(idDespesa)"
                                :class="idDespesa && String(type_id) === String(idDespesa)
                                    ? 'bg-red-50 border-red-400 text-red-700 ring-2 ring-red-400'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg border-2 text-sm font-medium transition focus:outline-none">
                                <span class="text-base leading-none">↓</span> Saída (despesa)
                            </button>
                            <button type="button"
                                @click="type_id = String(idReceita)"
                                :class="idReceita && String(type_id) === String(idReceita)
                                    ? 'bg-emerald-50 border-emerald-400 text-emerald-700 ring-2 ring-emerald-400'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg border-2 text-sm font-medium transition focus:outline-none">
                                <span class="text-base leading-none">↑</span> Entrada (receita)
                            </button>
                        </div>
                    </div>

                    {{-- Categoria --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Categoria</label>
                        <select name="category_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">...</option>
                        </select>
                    </div>
                </div>

                {{-- CARD 2 — PAGAMENTO + VALORES --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">Pagamento</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                        {{-- Valor --}}
                        <div x-data="{ raw: '', onInput(e) { const d = e.target.value.replace(/\D/g,''); const n = (parseInt(d||'0')/100); this.raw = n.toFixed(2); e.target.value = 'R$ ' + n.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); } }">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Valor (R$)</label>
                            <input type="text" inputmode="numeric"
                                x-ref="display"
                                x-on:input="onInput($event)"
                                placeholder="R$ 0,00"
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <input type="hidden" name="amount" id="amount" x-bind:value="raw">
                        </div>

                        {{-- Forma de pagamento --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Forma de pagamento</label>
                            <select name="payment_method_id" id="payment_method_id"
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">...</option>
                            </select>
                        </div>

                        {{-- Cartão (visível via JS quando forma = CC) --}}
                        <div id="credit-card-wrapper">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cartão</label>
                            <select name="credit_card_id" id="credit_card_id"
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Nenhum</option>
                            </select>
                        </div>

                    </div>

                    {{-- Valor total (só parcelado) --}}
                    <div x-show="installment_total > 1" x-transition>
                        <div x-data="{ raw: '', onInput(e) { const d = e.target.value.replace(/\D/g,''); const n = (parseInt(d||'0')/100); this.raw = n.toFixed(2); e.target.value = 'R$ ' + n.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); } }">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Valor total da compra (R$)</label>
                            <input type="text" inputmode="numeric"
                                x-ref="display"
                                x-on:input="onInput($event)"
                                placeholder="R$ 0,00"
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <input type="hidden" name="total_amount" id="total_amount" x-bind:value="raw">
                            <p class="mt-1 text-xs text-slate-400">Somatório de todas as parcelas (ex: 10 × 200 = 2.000).</p>
                        </div>
                    </div>
                </div>

                {{-- CARD 3 — PARCELAMENTO (só se parcelado) --}}
                <div x-show="installment_total > 1" x-transition
                     class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700">Parcelamento</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Parcela atual</label>
                            <input type="number" min="1" name="installment_number" value=""
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Total de parcelas</label>
                            <input type="number" min="1" name="installment_total" value=""
                                class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                {{-- CARD 4 — PARTICIPANTES --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                    <h3 class="text-sm font-semibold text-slate-700">Quem divide esse gasto?</h3>
                    <div id="users-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-2"></div>
                </div>

                {{-- BOTÕES --}}
                <div class="flex items-center justify-between">
                    <button id="delete-transaction" type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Excluir transação
                    </button>

                    <div class="flex gap-3">
                        <a href="{{ route('transactions.index') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Salvar alterações
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transactionId = Number(@json($transaction->id));
            const currentUserId = Number(@json(auth()->id()));
            const wrapper        = document.getElementById('edit-wrapper');
            const form           = document.getElementById('transaction-form');
            const errorsEl       = document.getElementById('transaction-errors');
            const successEl      = document.getElementById('transaction-success');
            const submitBtn      = form.querySelector('button[type="submit"]');
            const deleteBtn      = document.getElementById('delete-transaction');
            const editScopeCard  = document.getElementById('edit-scope-card');
            const editScopeInputs = editScopeCard.querySelectorAll('input[name="edit_scope"]');

            const paymentSelect      = document.getElementById('payment_method_id');
            const creditCardWrapper  = document.getElementById('credit-card-wrapper');
            const creditCardSelect   = document.getElementById('credit_card_id');
            const categorySelect     = document.querySelector('select[name="category_id"]');
            const usersGrid          = document.getElementById('users-grid');
            const descriptionInput   = document.querySelector('input[name="description"]');
            const amountInput        = document.querySelector('input[name="amount"]');
            const totalAmountInput   = document.querySelector('input[name="total_amount"]');
            const dateInput          = document.querySelector('input[name="transaction_date"]');
            const installmentNumberInput = document.querySelector('input[name="installment_number"]');
            const installmentTotalInput  = document.querySelector('input[name="installment_total"]');

            let creditCardMethodId = null;
            let transactionData    = null;
            let userListCache      = [];

            // Pré-popula o display de um input mascarado a partir do valor numérico
            function setCurrencyDisplay(hiddenInput, rawValue) {
                const number = parseFloat(rawValue || 0);
                if (number <= 0) return;
                const inputWrapper = hiddenInput.closest('[x-data]');
                if (!inputWrapper) return;
                const displayInput = inputWrapper.querySelector('input[type="text"]');
                if (displayInput) {
                    displayInput.value = 'R$ ' + number.toFixed(2)
                        .replace('.', ',')
                        .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }
                hiddenInput.value = number.toFixed(2);
            }

            const apiFetch = async (url, options = {}) => {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', ...(options.headers || {}) },
                    ...options,
                });
                if (!response.ok) throw new Error(`Erro ao carregar ${url}`);
                return response.json();
            };

            const fillSelect = (select, items, labelBuilder) => {
                select.querySelectorAll('option:not([value=""])').forEach((o) => o.remove());
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
                    label.className = 'inline-flex items-center gap-2 cursor-pointer';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'user_ids[]';
                    checkbox.value = user.id;
                    checkbox.className = 'rounded border-slate-300 text-green-600 focus:ring-green-500';

                    const shouldCheck = selectedIds.includes(Number(user.id))
                        || (selectedIds.length === 0 && currentUserId && Number(user.id) === currentUserId);
                    checkbox.checked = shouldCheck;

                    const span = document.createElement('span');
                    span.className = 'text-sm text-slate-700';
                    span.textContent = user.name;

                    label.appendChild(checkbox);
                    label.appendChild(span);
                    usersGrid.appendChild(label);
                });
            };

            const toggleCreditCard = () => {
                const methodId = creditCardMethodId ?? '1';
                creditCardWrapper.style.display = (paymentSelect.value == methodId) ? 'block' : 'none';
            };

            const clearErrors = () => {
                errorsEl.classList.add('hidden');
                errorsEl.innerHTML = '';
            };

            const showErrors = (errors) => {
                const messages = Object.values(errors || {}).flat();
                if (!messages.length) return;

                errorsEl.innerHTML = '';
                const title = document.createElement('div');
                title.className = 'font-semibold mb-1';
                title.textContent = 'Verifique os campos abaixo:';
                errorsEl.appendChild(title);

                const list = document.createElement('ul');
                list.className = 'list-disc list-inside space-y-0.5';
                messages.forEach((message) => {
                    const item = document.createElement('li');
                    item.textContent = message;
                    list.appendChild(item);
                });
                errorsEl.appendChild(list);
                errorsEl.classList.remove('hidden');
                errorsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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

                const typeList          = types.data ?? [];
                const categoryList      = categories.data ?? [];
                const paymentMethodList = paymentMethods.data ?? [];
                const creditCardList    = creditCards.data ?? [];
                const userList          = users.data ?? [];

                // Expor IDs de tipo para os botões visuais Alpine
                const alpineData = Alpine.$data(wrapper);
                const despesa = typeList.find(t => t.slug === 'dc');
                const receita = typeList.find(t => t.slug === 'rc');
                alpineData.idDespesa = despesa?.id ?? null;
                alpineData.idReceita = receita?.id ?? null;

                creditCardMethodId = paymentMethodList.find((item) => item.slug === 'cc')?.id ?? null;

                fillSelect(categorySelect, categoryList, (item) => item.name);
                fillSelect(paymentSelect, paymentMethodList, (item) => item.name);
                fillSelect(creditCardSelect, creditCardList, (item) => {
                    const ownerLabel = item.owner?.name ?? item.owner_name;
                    return ownerLabel ? `${item.name} (${ownerLabel})` : item.name;
                });

                userListCache = userList;
                renderUsers(userListCache, []);
            };

            const loadTransaction = async () => {
                const data = await apiFetch(`/api/transactions/${transactionId}`);
                transactionData = data.data ?? null;
                if (!transactionData) throw new Error('Transação não encontrada.');
            };

            const applyTransaction = () => {
                if (!transactionData) return;

                const alpineData = Alpine.$data(wrapper);

                // Preencher campos do formulário
                descriptionInput.value = transactionData.description ?? '';
                setCurrencyDisplay(amountInput, transactionData.amount ?? '');
                setCurrencyDisplay(totalAmountInput, transactionData.total_amount ?? '');
                dateInput.value = transactionData.transaction_date ?? '';
                installmentNumberInput.value = transactionData.installments?.number ?? '';
                installmentTotalInput.value  = transactionData.installments?.total ?? '';

                // Setar estado Alpine
                alpineData.type_id          = String(transactionData.type_id ?? '');
                alpineData.installment_total = transactionData.installments?.total ?? 0;

                categorySelect.value   = transactionData.category_id ?? '';
                paymentSelect.value    = transactionData.payment_method_id ?? '';
                creditCardSelect.value = transactionData.credit_card_id ?? '';

                // Mostrar/esconder card de escopo (conta fixa)
                if (transactionData.recurring_transaction_id) {
                    editScopeCard.style.display = 'block';
                    editScopeInputs.forEach((input) => { input.disabled = false; });
                } else {
                    editScopeCard.style.display = 'none';
                    editScopeInputs.forEach((input) => { input.disabled = true; });
                }

                // Participantes
                if (userListCache.length) {
                    const selectedIds = (transactionData.users ?? []).map((user) => Number(user.id));
                    renderUsers(userListCache, selectedIds);
                }

                // Visibilidade do cartão
                toggleCreditCard();

                // Atualizar título com contexto
                const heading = document.querySelector('[data-page-title]');
                if (heading && transactionData.description) {
                    heading.textContent = 'Editando: ' + transactionData.description;
                }
                document.title = 'Editando: ' + (transactionData.description ?? 'transação') + ' — Economyx';

                // Revelar formulário
                alpineData.ready = true;
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
                        headers: { Accept: 'application/json' },
                        body: formData,
                    });

                    if (response.status === 422) {
                        const data = await response.json();
                        showErrors(data.errors);
                        return;
                    }

                    if (!response.ok) throw new Error('Erro ao atualizar transação.');

                    successEl.classList.remove('hidden');
                    window.location.href = "{{ route('transactions.index') }}";
                } catch (error) {
                    showErrors({ general: [error.message || 'Erro inesperado ao salvar.'] });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar alterações';
                }
            });

            deleteBtn.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('request-delete', {
                    detail: {
                        callback: async () => {
                            deleteBtn.disabled = true;
                            deleteBtn.textContent = 'Excluindo...';
                            try {
                                const response = await fetch(`/api/transactions/${transactionId}`, {
                                    method: 'DELETE',
                                    headers: { Accept: 'application/json' },
                                });
                                if (!response.ok) throw new Error('Erro ao excluir transação.');
                                window.location.href = "{{ route('transactions.index') }}";
                            } catch (error) {
                                showErrors({ general: [error.message || 'Erro inesperado ao excluir.'] });
                                deleteBtn.disabled = false;
                                deleteBtn.textContent = 'Excluir transação';
                            }
                        }
                    }
                }));
            });

            Promise.all([loadTransaction(), loadOptions()])
                .then(applyTransaction)
                .catch((error) => {
                    showErrors({ general: [error.message || 'Erro ao carregar dados da transação.'] });
                });

            paymentSelect.addEventListener('change', toggleCreditCard);
        });
    </script>

</x-app-layout>
