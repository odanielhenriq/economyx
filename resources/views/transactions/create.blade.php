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

    <div class="" id="create-wrapper"
         x-data="{
             tipo: null,
             valorRaw: 0,
             type_id: '',
             payment_method_id: '',
             credit_card_id: '',
             parcelas: '',
             idDespesa: null,
             idReceita: null,
             creditCardMethodId: null,

             get isPontual()   { return this.tipo === 'pontual' },
             get isFixa()      { return this.tipo === 'fixa' },
             get isParcelado() { return this.tipo === 'parcelado' },
             get mostrarCartao() {
                 if (!this.creditCardMethodId) return false;
                 return String(this.payment_method_id) === String(this.creditCardMethodId);
             },
             get parcelaValor() {
                 const p = parseInt(this.parcelas);
                 if (!p || p < 2 || this.valorRaw <= 0) return null;
                 return (this.valorRaw / p).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
             },

             handleValorInput(e) {
                 const d = e.target.value.replace(/\D/g, '');
                 const n = parseInt(d || '0') / 100;
                 this.valorRaw = n;
                 e.target.value = 'R$ ' + n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
             },

             selectTipo(t) {
                 this.tipo = t;
                 this.credit_card_id = '';
                 this.payment_method_id = '';
                 this.parcelas = '';
             }
         }">

        {{-- Mensagens --}}
        <div id="transaction-errors"
            class="hidden mb-4 px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl"></div>

        {{-- Seletor de tipo --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">

            <button type="button" @click="selectTipo('pontual')"
                :class="tipo === 'pontual'
                    ? 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-500'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition cursor-pointer focus:outline-none">
                <span class="text-2xl">💸</span>
                <span class="text-sm font-semibold">Gasto ou receita</span>
                <span class="text-xs text-center opacity-70 leading-tight">Pagamento, salário, transferência</span>
            </button>

            <button type="button" @click="selectTipo('fixa')"
                :class="tipo === 'fixa'
                    ? 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-500'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition cursor-pointer focus:outline-none">
                <span class="text-2xl">🔁</span>
                <span class="text-sm font-semibold">Conta fixa</span>
                <span class="text-xs text-center opacity-70 leading-tight">Repete automaticamente todo mês</span>
            </button>

            <button type="button" @click="selectTipo('parcelado')"
                :class="tipo === 'parcelado'
                    ? 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-500'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition cursor-pointer focus:outline-none">
                <span class="text-2xl">💳</span>
                <span class="text-sm font-semibold">Parcelado no cartão</span>
                <span class="text-xs text-center opacity-70 leading-tight">Compra dividida em parcelas</span>
            </button>

        </div>

        {{-- Instrução inicial --}}
        <div x-show="!tipo" class="text-center py-16 text-slate-400">
            <p class="text-sm">Selecione acima o tipo de lançamento para continuar</p>
        </div>

        {{-- Formulário --}}
        <form id="transaction-form" x-show="tipo" x-transition class="space-y-4">
            @csrf

            {{-- Card 1 — Informações básicas (comuns aos 3 tipos) --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Informações básicas</h3>

                {{-- Descrição --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
                    <input type="text" name="description"
                        class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Ex: Mercado, aluguel, salário...">
                </div>

                {{-- Valor + Data --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            <span x-show="!isParcelado">Valor (R$)</span>
                            <span x-show="isParcelado">Valor total da compra (R$)</span>
                        </label>
                        <input type="text" inputmode="numeric" id="valor-display"
                            @input="handleValorInput($event)"
                            placeholder="R$ 0,00"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <input type="hidden" name="total_amount" :value="valorRaw.toFixed(2)">
                        <input type="hidden" name="amount" :value="isParcelado && parseInt(parcelas) >= 2 ? (valorRaw / parseInt(parcelas)).toFixed(2) : valorRaw.toFixed(2)">
                    </div>
                    <div x-show="!isFixa">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Data</label>
                        <input type="date" name="transaction_date"
                            value="{{ now()->toDateString() }}"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                {{-- É uma entrada ou saída? --}}
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
                    <select id="category_id" name="category_id"
                        class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Selecione...</option>
                    </select>
                </div>
            </div>

            {{-- Card 2a — Como foi pago? (Pontual) --}}
            <div x-show="isPontual" x-transition
                 class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Como foi pago?</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Forma de pagamento</label>
                        <select id="payment_method_id" x-model="payment_method_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div x-show="mostrarCartao" x-transition>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cartão</label>
                        <select id="credit_card_id_pontual" x-model="credit_card_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione o cartão</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Card 2b — Configuração da conta fixa --}}
            <div x-show="isFixa" x-transition
                 class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">

                <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg border border-green-200">
                    <span class="text-green-600 mt-0.5 flex-shrink-0">🔁</span>
                    <div>
                        <p class="text-sm font-medium text-green-800">Esta conta vai aparecer automaticamente todo mês</p>
                        <p class="text-xs text-green-600 mt-0.5">Você pode editar ou pausar quando quiser em Contas fixas no menu.</p>
                    </div>
                </div>

                <h3 class="text-sm font-semibold text-slate-700">Configuração</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Com que frequência?</label>
                        <select id="frequency"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="monthly">Todo mês</option>
                            <option value="yearly">Uma vez por ano</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Dia do vencimento</label>
                        <input type="number" id="day_of_month" min="1" max="31"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Ex: 10">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">A partir de quando?</label>
                        <input type="date" id="start_date"
                            value="{{ now()->toDateString() }}"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Até quando? (opcional)</label>
                        <input type="date" id="end_date"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Forma de pagamento</label>
                        <select id="payment_method_id_fixa"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cartão de crédito (opcional)</label>
                        <select id="credit_card_id_fixa"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Sem cartão</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Card 2c — Parcelado no cartão --}}
            <div x-show="isParcelado" x-transition
                 class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Detalhes do parcelamento</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cartão de crédito</label>
                        <select id="credit_card_id_parcelado" x-model="credit_card_id"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione o cartão</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Em quantas parcelas?</label>
                        <input type="number" min="2" max="48"
                            x-model="parcelas"
                            placeholder="Ex: 6"
                            class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <div x-show="parcelaValor" x-transition
                     class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <p class="text-sm text-slate-700">
                        <span class="font-semibold text-slate-900"
                              x-text="parcelas + 'x de ' + parcelaValor"></span>
                        no cartão selecionado
                    </p>
                    <p class="text-xs text-slate-400 mt-1">A primeira parcela entra na fatura do mês selecionado</p>
                </div>
            </div>

            {{-- Card 3 — Quem divide esse gasto? --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                <h3 class="text-sm font-semibold text-slate-700">Quem divide esse gasto?</h3>
                <div id="users-grid" class="grid grid-cols-2 md:grid-cols-3 gap-2"></div>
            </div>

            {{-- Ações --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('transactions.index') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition">
                    Cancelar
                </a>
                <button type="submit" id="submit-btn"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Salvar
                </button>
            </div>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const currentUserId = Number(@json(auth()->id()));
            const form          = document.getElementById('transaction-form');
            const errorsEl      = document.getElementById('transaction-errors');
            const submitBtn     = document.getElementById('submit-btn');
            const usersGrid     = document.getElementById('users-grid');
            const wrapper       = document.getElementById('create-wrapper');

            const categorySelect          = document.getElementById('category_id');
            const paymentMethodSelect     = document.getElementById('payment_method_id');
            const paymentMethodFixaSelect = document.getElementById('payment_method_id_fixa');
            const creditCardPontualSelect = document.getElementById('credit_card_id_pontual');
            const creditCardParceladoSelect = document.getElementById('credit_card_id_parcelado');
            const creditCardFixaSelect    = document.getElementById('credit_card_id_fixa');

            // ── Helpers ──────────────────────────────────────────────────────

            const apiFetch = async (url) => {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(`Erro ao carregar ${url}`);
                return res.json();
            };

            const fillSelect = (select, items, labelFn) => {
                select.querySelectorAll('option:not([value=""])').forEach(o => o.remove());
                items.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = labelFn(item);
                    select.appendChild(opt);
                });
            };

            const renderUsers = (users) => {
                usersGrid.innerHTML = '';
                users.forEach(user => {
                    const label = document.createElement('label');
                    label.className = 'inline-flex items-center gap-2 cursor-pointer';
                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.name = 'user_ids[]';
                    cb.value = user.id;
                    cb.className = 'rounded border-slate-300 text-green-600 focus:ring-green-500';
                    cb.checked = !!(currentUserId && Number(user.id) === currentUserId);
                    const span = document.createElement('span');
                    span.className = 'text-sm text-slate-700';
                    span.textContent = user.name;
                    label.appendChild(cb);
                    label.appendChild(span);
                    usersGrid.appendChild(label);
                });
            };

            const clearErrors = () => {
                errorsEl.classList.add('hidden');
                errorsEl.innerHTML = '';
            };

            const showErrors = (errors) => {
                const msgs = Object.values(errors || {}).flat();
                if (!msgs.length) return;
                errorsEl.innerHTML = '<div class="font-semibold mb-1">Verifique os campos abaixo:</div>';
                const ul = document.createElement('ul');
                ul.className = 'list-disc list-inside space-y-0.5';
                msgs.forEach(m => {
                    const li = document.createElement('li');
                    li.textContent = m;
                    ul.appendChild(li);
                });
                errorsEl.appendChild(ul);
                errorsEl.classList.remove('hidden');
                errorsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            };

            // ── Carrega opções via API ────────────────────────────────────────

            const loadOptions = async () => {
                const userQuery = currentUserId ? `?user_id=${currentUserId}` : '';
                const [types, categories, paymentMethods, creditCards, users] = await Promise.all([
                    apiFetch('/api/types'),
                    apiFetch('/api/categories'),
                    apiFetch('/api/payment-methods'),
                    apiFetch('/api/credit-cards'),
                    apiFetch(`/api/users${userQuery}`),
                ]);

                const typeList    = types.data ?? [];
                const catList     = categories.data ?? [];
                const pmList      = paymentMethods.data ?? [];
                const cardList    = creditCards.data ?? [];
                const userList    = users.data ?? [];

                // Expor IDs para o Alpine
                const alpineData = Alpine.$data(wrapper);
                const despesa    = typeList.find(t => t.slug === 'dc');
                const receita    = typeList.find(t => t.slug === 'rc');
                const ccMethod   = pmList.find(m => m.slug === 'cc');

                alpineData.idDespesa        = despesa?.id  ?? null;
                alpineData.idReceita        = receita?.id  ?? null;
                alpineData.creditCardMethodId = ccMethod?.id ?? null;

                // Pré-selecionar despesa como padrão
                if (despesa) alpineData.type_id = String(despesa.id);

                fillSelect(categorySelect, catList, i => i.name);

                const cardLabel = i => {
                    const owner = i.owner?.name ?? i.owner_name;
                    return owner ? `${i.name} (${owner})` : i.name;
                };

                [paymentMethodSelect, paymentMethodFixaSelect].forEach(sel => {
                    fillSelect(sel, pmList, i => i.name);
                });

                [creditCardPontualSelect, creditCardParceladoSelect, creditCardFixaSelect].forEach(sel => {
                    fillSelect(sel, cardList, cardLabel);
                });

                renderUsers(userList);
            };

            // ── Submit ────────────────────────────────────────────────────────

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearErrors();

                const alpineData = Alpine.$data(wrapper);
                const tipo       = alpineData.tipo;
                if (!tipo) return;

                // Validações comuns
                const valorRaw  = alpineData.valorRaw;
                const categoria = categorySelect.value;
                const typeId    = alpineData.type_id;
                const userIds   = [...form.querySelectorAll('input[name="user_ids[]"]:checked')].map(cb => cb.value);

                const erros = {};
                if (!valorRaw || valorRaw <= 0) erros.valor    = ['O valor é obrigatório e deve ser maior que zero.'];
                if (!categoria)                 erros.categoria = ['A categoria é obrigatória.'];
                if (!typeId)                    erros.tipo      = ['Selecione se é uma saída ou uma entrada.'];
                if (!userIds.length)            erros.users     = ['Selecione pelo menos um participante.'];

                if (tipo === 'parcelado') {
                    const p = parseInt(alpineData.parcelas);
                    if (!p || p < 2)              erros.parcelas = ['Informe o número de parcelas (mínimo 2).'];
                    if (!alpineData.credit_card_id) erros.cartao   = ['Selecione o cartão para compra parcelada.'];
                }

                if (tipo === 'pontual' && alpineData.mostrarCartao && !alpineData.credit_card_id) {
                    erros.cartao = ['Selecione o cartão quando a forma de pagamento for cartão de crédito.'];
                }

                if (Object.keys(erros).length) { showErrors(erros); return; }

                submitBtn.disabled    = true;
                submitBtn.textContent = 'Salvando...';

                try {
                    if (tipo === 'fixa') {
                        await submitRecorrente(alpineData, userIds);
                    } else {
                        await submitTransacao(alpineData, tipo, userIds);
                    }
                } catch (err) {
                    showErrors({ geral: [err.message || 'Erro inesperado ao salvar.'] });
                } finally {
                    submitBtn.disabled    = false;
                    submitBtn.textContent = 'Salvar';
                }
            });

            // ── Submit conta fixa → /api/recurring-templates ─────────────────

            async function submitRecorrente(alpineData, userIds) {
                const pmFixa = paymentMethodFixaSelect.value;
                if (!pmFixa) {
                    showErrors({ pagamento: ['A forma de pagamento é obrigatória.'] });
                    return;
                }

                const body = new FormData();
                body.append('description',        form.querySelector('input[name="description"]').value);
                body.append('amount',             alpineData.valorRaw.toFixed(2));
                body.append('frequency',          document.getElementById('frequency').value);
                body.append('category_id',        categorySelect.value);
                body.append('type_id',            alpineData.type_id);
                body.append('payment_method_id',  pmFixa);
                body.append('is_active',          '1');

                const dayOfMonth = document.getElementById('day_of_month').value;
                const startDate  = document.getElementById('start_date').value;
                const endDate    = document.getElementById('end_date').value;
                const ccFixa     = creditCardFixaSelect.value;

                if (dayOfMonth) body.append('day_of_month',  dayOfMonth);
                if (startDate)  body.append('start_date',    startDate);
                if (endDate)    body.append('end_date',       endDate);
                if (ccFixa)     body.append('credit_card_id', ccFixa);

                userIds.forEach(id => body.append('user_ids[]', id));

                const res = await fetch('/api/recurring-templates', {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body,
                });

                if (res.status === 422) {
                    const data = await res.json();
                    showErrors(data.errors ?? {});
                    return;
                }
                if (!res.ok) throw new Error('Erro ao criar conta fixa.');

                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'Conta fixa criada! Ela vai aparecer automaticamente todo mês.', type: 'success' }
                }));
                window.location.href = "{{ route('transactions.index') }}";
            }

            // ── Submit transação → /api/transactions ─────────────────────────

            async function submitTransacao(alpineData, tipo, userIds) {
                const parcelas    = parseInt(alpineData.parcelas);
                const isParcelado = tipo === 'parcelado' && parcelas >= 2;
                const amount      = isParcelado
                    ? (alpineData.valorRaw / parcelas).toFixed(2)
                    : alpineData.valorRaw.toFixed(2);

                const pmId   = isParcelado
                    ? (alpineData.creditCardMethodId ?? alpineData.payment_method_id)
                    : alpineData.payment_method_id;
                const cardId = isParcelado
                    ? alpineData.credit_card_id
                    : (alpineData.mostrarCartao ? alpineData.credit_card_id : '');

                const body = new FormData();
                body.append('description',         form.querySelector('input[name="description"]').value);
                body.append('amount',              amount);
                body.append('total_amount',        alpineData.valorRaw.toFixed(2));
                body.append('transaction_date',    form.querySelector('input[name="transaction_date"]').value);
                body.append('category_id',         categorySelect.value);
                body.append('type_id',             alpineData.type_id);
                body.append('payment_method_id',   String(pmId));
                if (cardId) body.append('credit_card_id', cardId);

                if (isParcelado) {
                    body.append('installment_number', '1');
                    body.append('installment_total',  String(parcelas));
                }

                userIds.forEach(id => body.append('user_ids[]', id));

                const res = await fetch('/api/transactions', {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body,
                });

                if (res.status === 422) {
                    const data = await res.json();
                    showErrors(data.errors ?? {});
                    return;
                }
                if (!res.ok) throw new Error('Erro ao salvar transação.');

                window.location.href = "{{ route('transactions.index') }}";
            }

            // ── Init ─────────────────────────────────────────────────────────

            loadOptions().catch(err => {
                showErrors({ geral: [err.message || 'Erro ao carregar dados do formulário.'] });
            });
        });
    </script>

</x-app-layout>
