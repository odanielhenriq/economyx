{{-- resources/views/transactions/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" x-data="exportModal()">
            <h1 class="text-lg font-semibold text-slate-900">Transações</h1>
            <div class="flex items-center gap-3">
                {{-- Botão Exportar --}}
                <button
                    @click="open = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exportar
                </button>

                {{-- Botão Nova transação --}}
                <a href="{{ route('transactions.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nova transação
                </a>
            </div>

            {{-- Modal de exportação --}}
            <div
                x-show="open"
                x-on:keydown.escape.window="open = false"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="display:none">
                <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                <div class="relative bg-white rounded-xl shadow-sm border border-slate-200 w-full max-w-md p-6" @click.stop>

                    <h2 class="text-base font-semibold text-slate-900 mb-1">Exportar transações</h2>
                    <p class="text-sm text-slate-500 mb-5">Escolha o período e baixe um arquivo CSV.</p>

                    {{-- Presets --}}
                    <div class="flex flex-wrap gap-2 mb-5">
                        <template x-for="preset in presets" :key="preset.label">
                            <button
                                type="button"
                                @click="applyPreset(preset)"
                                :class="startDate === preset.start && endDate === preset.end
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                x-text="preset.label">
                            </button>
                        </template>
                    </div>

                    {{-- Inputs de data --}}
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">De</label>
                            <input type="date" x-model="startDate"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Até</label>
                            <input type="date" x-model="endDate"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" />
                        </div>
                    </div>

                    {{-- Ações --}}
                    <div class="flex justify-end gap-3">
                        <button type="button"
                                @click="open = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                            Cancelar
                        </button>
                        <button type="button"
                                @click="exportar()"
                                :disabled="!startDate || !endDate || loading"
                                :class="(!startDate || !endDate || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg transition">
                            <svg x-show="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span x-text="loading ? 'Gerando...' : 'Baixar CSV'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Alertas de sessão --}}
        @if (session('success'))
            <div class="px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">

                <div>
                    <label for="filter-month" class="block text-xs font-medium text-slate-500 mb-1">Mês</label>
                    <input type="month" id="filter-month"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label for="filter-user" class="block text-xs font-medium text-slate-500 mb-1">Pessoa</label>
                    <select id="filter-user"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todas</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-category" class="block text-xs font-medium text-slate-500 mb-1">Categoria</label>
                    <select id="filter-category"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-type" class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
                    <select id="filter-type"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todos</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-payment-method" class="block text-xs font-medium text-slate-500 mb-1">Pagamento</label>
                    <select id="filter-payment-method"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todas</option>
                        @foreach ($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button id="filter-apply"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Filtrar
                    </button>
                    <button id="filter-clear"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                        Limpar
                    </button>
                </div>

            </div>
        </div>

        {{-- Wrapper com loading skeleton + conteúdo real --}}
        <div id="transactions-table-wrapper" x-data="{ loading: true }">

            {{-- Skeleton --}}
            <div x-show="loading" class="space-y-3">
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
            </div>

            {{-- Conteúdo real --}}
            <div x-show="!loading" style="display:none" class="space-y-4">

                <div id="transactions-state" class=""></div>

                {{-- Cards de resumo --}}
                <div id="transactions-summary" class="grid grid-cols-1 gap-4 md:grid-cols-3"></div>

                {{-- Tabela --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left divide-y divide-slate-100 min-w-[640px]">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoria</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Data</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Valor</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Minha parte</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pessoas</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Parcelas</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-body" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Paginação --}}
                <div class="flex items-center justify-between text-sm text-slate-500">
                    <button id="prev-page"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition disabled:opacity-40 disabled:cursor-not-allowed"
                        disabled>
                        ← Anterior
                    </button>
                    <span id="pagination-info" class="text-xs text-slate-500">Página 1 de 1</span>
                    <button id="next-page"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Próxima →
                    </button>
                </div>

            </div>
        </div>

    </div>

    {{-- FAB mobile — botão flutuante de nova transação --}}
    <a href="{{ route('transactions.create') }}"
       class="lg:hidden fixed bottom-6 right-6 z-50
              w-14 h-14 bg-green-600 hover:bg-green-700
              rounded-full shadow-lg flex items-center justify-center
              text-white transition active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 4v16m8-8H4" />
        </svg>
    </a>

    <script type="module">
        const stateEl = document.getElementById('transactions-state');
        const bodyEl = document.getElementById('transactions-body');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const paginationInfoEl = document.getElementById('pagination-info');
        const summaryEl = document.getElementById('transactions-summary');

        const filterMonthEl = document.getElementById('filter-month');
        const filterUserEl = document.getElementById('filter-user');
        const filterCategoryEl = document.getElementById('filter-category');
        const filterTypeEl = document.getElementById('filter-type');
        const filterPaymentMethodEl = document.getElementById('filter-payment-method');
        const filterApplyBtn = document.getElementById('filter-apply');
        const filterClearBtn = document.getElementById('filter-clear');

        let currentPage = 1;
        const perPage = 10;
        let currentFilters = {
            month: '',
            user_id: '',
            category_id: '',
            type_id: '',
            payment_method_id: '',
        };

        async function loadTransactions(page = 1, filters = {}) {
            Alpine.$data(document.getElementById('transactions-table-wrapper')).loading = true;

            try {
                const params = new URLSearchParams();
                params.set('per_page', perPage);
                params.set('page', page);

                if (filters.month)             params.set('month', filters.month);
                if (filters.user_id)           params.set('user_id', filters.user_id);
                if (filters.category_id)       params.set('category_id', filters.category_id);
                if (filters.type_id)           params.set('type_id', filters.type_id);
                if (filters.payment_method_id) params.set('payment_method_id', filters.payment_method_id);

                const response = await fetch(`/api/transactions?${params.toString()}`);
                if (!response.ok) throw new Error('Erro ao carregar transações');

                const json = await response.json();
                const items = json.data ?? [];
                const meta = json.meta ?? null;
                const links = json.links ?? null;

                bodyEl.innerHTML = '';
                summaryEl.innerHTML = '';

                if (items.length === 0) {
                    stateEl.innerHTML = `
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-3 text-sm font-semibold text-slate-800">Nenhuma transação encontrada</h3>
                            <p class="mt-1 text-sm text-slate-400">Tente ajustar os filtros ou adicione uma nova transação.</p>
                            <div class="mt-5">
                                <a href="{{ route('transactions.create') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                                    + Nova transação
                                </a>
                            </div>
                        </div>
                    `;
                    paginationInfoEl.textContent = '';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                    return;
                }

                stateEl.innerHTML = '';

                // Resumo da página
                let totalIncome = 0;
                let totalExpense = 0;
                items.forEach(tx => {
                    const signed = Number(tx.signed_amount);
                    if (signed > 0) totalIncome += signed;
                    if (signed < 0) totalExpense += signed;
                });
                const balance = totalIncome + totalExpense;

                summaryEl.innerHTML = `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Receitas</div>
                        <div class="text-xl font-bold text-emerald-700 tabular-nums">
                            ${totalIncome.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Despesas</div>
                        <div class="text-xl font-bold text-red-600 tabular-nums">
                            ${totalExpense.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Saldo</div>
                        <div class="text-xl font-bold tabular-nums ${balance >= 0 ? 'text-emerald-700' : 'text-red-600'}">
                            ${balance.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                `;

                // Tabela
                items.forEach(tx => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50';

                    const isNegative = Number(tx.signed_amount) < 0;
                    const amountFormatted = Number(tx.signed_amount).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    const totalAmount = tx.total_amount ?? null;
                    const totalFormatted = totalAmount !== null
                        ? Number(totalAmount).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                        : null;
                    const perUserFormatted = tx.totals?.per_user_share != null
                        ? Number(tx.totals.per_user_share).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                        : '-';

                    const ownerLabel = tx.credit_card?.owner_label || tx.credit_card?.owner_name || '';
                    const infoBadges = `
                        <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-slate-100 text-slate-600">
                            ${tx.category?.name ?? '-'}
                        </span>
                        ${
                            tx.payment_method?.name === 'Credit Card' && tx.credit_card?.name
                                ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-purple-100 text-purple-700">
                                    ${tx.credit_card.name}${ownerLabel ? ' (' + ownerLabel + ')' : ''}
                                   </span>`
                                : ''
                        }
                    `;

                    const usersDetailHtml = (tx.users ?? [])
                        .map(u => {
                            const share = Number(u.share_amount ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                            return `<div class="flex items-center justify-between gap-2 text-xs">
                                        <span class="text-slate-700">${u.name}</span>
                                        <span class="text-slate-400 tabular-nums">${share}</span>
                                    </div>`;
                        })
                        .join('');

                    const installmentLabel = tx.installments?.is_installment
                        ? `<button
                              data-installment-id="${tx.id}"
                              class="installment-btn inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                     font-medium bg-purple-100 text-purple-700 hover:bg-purple-200 transition cursor-pointer"
                              title="Ver todas as parcelas">
                              ${tx.installments.label}
                           </button>
                           <div class="text-xs text-slate-400 mt-0.5">faltam ${tx.installments.remaining}</div>`
                        : '-';

                    tr.innerHTML = `
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-wrap gap-1">${infoBadges}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-500 align-top whitespace-nowrap">${tx.date}</td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium text-slate-800">${tx.description ?? '(sem descrição)'}</span>
                                ${tx.recurring_transaction_id ? '<span class="text-xs text-slate-400" title="Conta fixa">🔁</span>' : ''}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right align-top">
                            <span class="font-semibold tabular-nums ${isNegative ? 'text-red-600' : 'text-emerald-700'}">
                                ${amountFormatted}
                            </span>
                            ${totalFormatted && tx.installments?.is_installment
                                ? `<div class="text-xs text-slate-400 tabular-nums">Total: ${totalFormatted}</div>`
                                : ''}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600 tabular-nums align-top">${perUserFormatted}</td>
                        <td class="px-4 py-3 align-top">${usersDetailHtml || '<span class="text-slate-400">-</span>'}</td>
                        <td class="px-4 py-3 text-slate-500 align-top text-xs">${installmentLabel}</td>
                        <td class="px-4 py-3 text-center align-top">
                            <div class="flex items-center justify-center gap-1">
                                <a href="/transactions/${tx.id}/edit"
                                   class="inline-flex items-center p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                   title="Editar">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                    </svg>
                                </a>
                                <button data-id="${tx.id}"
                                    class="delete-btn inline-flex items-center p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                    title="Excluir">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    `;

                    bodyEl.appendChild(tr);
                });

                // Paginação
                if (meta) {
                    currentPage = meta.current_page;
                    paginationInfoEl.textContent = `Página ${meta.current_page} de ${meta.last_page}`;
                    prevBtn.disabled = !(links && links.prev);
                    nextBtn.disabled = !(links && links.next);
                } else {
                    paginationInfoEl.textContent = '';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                }

            } catch (error) {
                console.error(error);
                stateEl.innerHTML = '<div class="text-sm text-slate-500">Erro ao carregar transações.</div>';
                bodyEl.innerHTML = '';
                summaryEl.innerHTML = '';
                paginationInfoEl.textContent = '';
                prevBtn.disabled = true;
                nextBtn.disabled = true;
            } finally {
                Alpine.$data(document.getElementById('transactions-table-wrapper')).loading = false;
            }
        }

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) loadTransactions(currentPage - 1, currentFilters);
        });

        nextBtn.addEventListener('click', () => {
            loadTransactions(currentPage + 1, currentFilters);
        });

        filterApplyBtn.addEventListener('click', () => {
            currentFilters = {
                month: filterMonthEl.value || '',
                user_id: filterUserEl.value || '',
                category_id: filterCategoryEl.value || '',
                type_id: filterTypeEl.value || '',
                payment_method_id: filterPaymentMethodEl.value || '',
            };
            loadTransactions(1, currentFilters);
        });

        filterClearBtn.addEventListener('click', () => {
            filterMonthEl.value = '';
            filterUserEl.value = '';
            filterCategoryEl.value = '';
            filterTypeEl.value = '';
            filterPaymentMethodEl.value = '';
            currentFilters = { month: '', user_id: '', category_id: '', type_id: '', payment_method_id: '' };
            loadTransactions(1, currentFilters);
        });

        document.addEventListener('click', (e) => {
            const installBtn = e.target.closest('.installment-btn');
            if (installBtn) {
                const id = installBtn.dataset.installmentId;
                window.dispatchEvent(new CustomEvent('open-installments', { detail: { id } }));
                return;
            }
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.delete-btn');
            if (!btn) return;

            const id = btn.dataset.id;
            window.dispatchEvent(new CustomEvent('request-delete', {
                detail: {
                    callback: async () => {
                        try {
                            const response = await fetch(`/api/transactions/${id}`, {
                                method: 'DELETE',
                                headers: { 'Accept': 'application/json' },
                            });

                            if (!response.ok) {
                                window.dispatchEvent(new CustomEvent('toast', {
                                    detail: { message: 'Erro ao excluir transação.', type: 'error' }
                                }));
                                return;
                            }

                            await loadTransactions(currentPage, currentFilters);
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: 'Transação excluída com sucesso!', type: 'success' }
                            }));
                        } catch (err) {
                            console.error(err);
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: 'Erro inesperado ao excluir.', type: 'error' }
                            }));
                        }
                    }
                }
            }));
        });

        loadTransactions(1, currentFilters);
    </script>

    {{-- Modal de parcelas --}}
    <div
        x-data="{
            open: false,
            loading: false,
            description: '',
            installments: [],
            init() {
                window.addEventListener('open-installments', async (e) => {
                    this.open = true;
                    this.loading = true;
                    this.installments = [];
                    this.description = '';
                    try {
                        const res = await fetch('/api/transactions/' + e.detail.id + '/installments');
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        this.description  = data.description ?? '';
                        this.installments = Array.isArray(data.installments) ? data.installments : [];
                    } catch {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: 'Erro ao carregar parcelas.', type: 'error' }
                        }));
                        this.open = false;
                    } finally {
                        this.loading = false;
                    }
                });
            }
        }"
        x-show="open"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none">

        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <div class="relative bg-white rounded-xl border border-slate-200 shadow-sm w-full max-w-lg p-6" @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-semibold text-slate-900" x-text="description"></h2>
                    <p class="text-sm text-slate-500 mt-0.5" x-text="installments.length + ' parcelas no total'"></p>
                </div>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Skeleton --}}
            <div x-show="loading" class="space-y-3">
                <template x-for="i in 4">
                    <div class="h-12 bg-slate-100 rounded-lg animate-pulse"></div>
                </template>
            </div>

            {{-- Lista de parcelas --}}
            <div x-show="!loading" class="space-y-2 max-h-96 overflow-y-auto pr-1">
                <template x-for="inst in installments" :key="inst.id">
                    <div
                        :class="{
                            'bg-green-50 border-green-200': inst.is_past && !inst.is_current,
                            'bg-indigo-50 border-indigo-300 ring-1 ring-indigo-300': inst.is_current,
                            'bg-white border-slate-200': !inst.is_past && !inst.is_current,
                        }"
                        class="flex items-center justify-between px-4 py-3 rounded-lg border transition">

                        <div class="flex items-center gap-3">
                            <div
                                :class="{
                                    'bg-green-500': inst.is_past && !inst.is_current,
                                    'bg-indigo-500': inst.is_current,
                                    'bg-slate-200': !inst.is_past && !inst.is_current,
                                }"
                                class="w-2 h-2 rounded-full flex-shrink-0"></div>
                            <div>
                                <p class="text-sm font-medium text-slate-900"
                                   x-text="'Parcela ' + inst.installment_number + ' de ' + inst.total"></p>
                                <p class="text-xs text-slate-400"
                                   x-text="inst.due_date
                                       ? new Date(inst.due_date + 'T12:00:00').toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
                                       : '-'"></p>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-sm font-semibold tabular-nums"
                               :class="{
                                   'text-green-700': inst.is_past && !inst.is_current,
                                   'text-indigo-700': inst.is_current,
                                   'text-slate-500': !inst.is_past && !inst.is_current,
                               }"
                               x-text="'R$ ' + Number(inst.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })"></p>
                            <p class="text-xs mt-0.5"
                               :class="{
                                   'text-green-500': inst.is_past && !inst.is_current,
                                   'text-indigo-500': inst.is_current,
                                   'text-slate-400': !inst.is_past && !inst.is_current,
                               }"
                               x-text="inst.is_current ? 'Mês atual' : inst.is_past ? 'Pago' : 'Futuro'"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function exportModal() {
            return {
                open: false,
                startDate: '',
                endDate: '',
                loading: false,
                presets: [
                    {
                        label: 'Este mês',
                        start: '{{ now()->startOfMonth()->format('Y-m-d') }}',
                        end:   '{{ now()->endOfMonth()->format('Y-m-d') }}',
                    },
                    {
                        label: 'Mês passado',
                        start: '{{ now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d') }}',
                        end:   '{{ now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d') }}',
                    },
                    {
                        label: 'Últimos 3 meses',
                        start: '{{ now()->subMonths(3)->startOfMonth()->format('Y-m-d') }}',
                        end:   '{{ now()->format('Y-m-d') }}',
                    },
                    {
                        label: 'Este ano',
                        start: '{{ now()->startOfYear()->format('Y-m-d') }}',
                        end:   '{{ now()->format('Y-m-d') }}',
                    },
                ],
                applyPreset(preset) {
                    this.startDate = preset.start;
                    this.endDate   = preset.end;
                },
                exportar() {
                    if (!this.startDate || !this.endDate) return;
                    this.loading = true;
                    const url = '{{ route('transactions.export') }}'
                        + '?start_date=' + this.startDate
                        + '&end_date='   + this.endDate;
                    window.location.href = url;
                    setTimeout(() => { this.loading = false; }, 1500);
                },
            };
        }
    </script>

</x-app-layout>
