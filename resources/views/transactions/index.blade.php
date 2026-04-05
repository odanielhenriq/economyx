{{-- resources/views/transactions/index.blade.php --}}
<x-app-layout>
    {{-- SLOT DO CABEÇALHO DA LAYOUT --}}
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Transações') }}
            </h2>

            {{-- Botão para ir para a tela de criação de transação --}}
            <a href="{{ route('transactions.create') }}"
                class="px-3 py-1 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                + Nova transação
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 space-y-6">

                    {{-- ALERTAS DE SESSÃO (mensagens flash, sucesso ou erro de rota web) --}}
                    @if (session('success'))
                        <div class="px-4 py-2 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="px-4 py-2 text-sm text-red-800 bg-red-100 border border-red-200 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ============================
                         FILTROS DA LISTAGEM
                       ============================ --}}
                    <div class="flex flex-col gap-3 mb-4 md:flex-row md:flex-wrap md:items-end">

                        {{-- Filtro por mês (competência: ano-mês) --}}
                        <div>
                            <label for="filter-month" class="text-sm text-gray-600">Mês</label>
                            <input type="month" id="filter-month"
                                class="block w-full mt-1 text-sm border-gray-300 rounded">
                        </div>

                        {{-- Filtro por pessoa (user ligado à transação) --}}
                        <div>
                            <label for="filter-user" class="text-sm text-gray-600">Pessoa</label>
                            <select id="filter-user" class="block w-full mt-1 text-sm border-gray-300 rounded">
                                <option value="">Todas</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtro por categoria --}}
                        <div>
                            <label for="filter-category" class="text-sm text-gray-600">Categoria</label>
                            <select id="filter-category" class="block w-full mt-1 text-sm border-gray-300 rounded">
                                <option value="">Todas</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtro por tipo (receita / despesa etc.) --}}
                        <div>
                            <label for="filter-type" class="text-sm text-gray-600">Tipo</label>
                            <select id="filter-type" class="block w-full mt-1 text-sm border-gray-300 rounded">
                                <option value="">Todos</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtro por forma de pagamento --}}
                        <div>
                            <label for="filter-payment-method" class="text-sm text-gray-600">Forma de pagamento</label>
                            <select id="filter-payment-method"
                                class="block w-full mt-1 text-sm border-gray-300 rounded">
                                <option value="">Todas</option>
                                @foreach ($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Botões para aplicar/limpar filtros (só mexem em JS, não recarregam a página) --}}
                        <div class="flex gap-2 mt-2">
                            <button id="filter-apply"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                Filtrar
                            </button>

                            <button id="filter-clear"
                                class="px-4 py-2 text-sm text-gray-700 bg-gray-100 border rounded hover:bg-gray-200">
                                Limpar
                            </button>
                        </div>

                    </div>

                    {{-- Wrapper com loading skeleton + conteúdo real --}}
                    <div id="transactions-table-wrapper" x-data="{ loading: true }">

                        {{-- Skeleton: visível enquanto carrega --}}
                        <div x-show="loading" class="space-y-3">
                            <div class="h-10 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-10 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-10 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-10 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-10 bg-gray-200 rounded animate-pulse"></div>
                        </div>

                        {{-- Conteúdo real: visível após carregar --}}
                        <div x-show="!loading" style="display:none">

                            {{-- Texto de estado (erro, vazio etc.) --}}
                            <div id="transactions-state" class="mb-4"></div>

                            {{-- Cards de resumo da página atual (receitas, despesas, saldo) --}}
                            <div id="transactions-summary" class="grid grid-cols-1 gap-4 mb-6 text-sm md:grid-cols-3">
                                {{-- preenchido via JS --}}
                            </div>

                            {{-- TABELA PRINCIPAL --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm text-left">
                                    <thead class="border-b text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2">Info</th>
                                            <th class="px-3 py-2">Data</th>
                                            <th class="px-3 py-2">Descrição</th>
                                            <th class="px-3 py-2 text-right">Valor</th>
                                            <th class="px-3 py-2 text-right">Cota p/ pessoa</th>
                                            <th class="px-3 py-2">Pessoas</th>
                                            <th class="px-3 py-2">Parcelas</th>
                                            <th class="px-3 py-2 text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactions-body" class="divide-y">
                                        {{-- Linhas inseridas dinamicamente via JS a partir da API /api/transactions --}}
                                    </tbody>
                                </table>
                            </div>

                            {{-- Controles de PAGINAÇÃO da API (página anterior / próxima) --}}
                            <div class="flex items-center justify-between mt-4 text-sm text-gray-500">
                                <button id="prev-page" class="px-3 py-1 border rounded disabled:opacity-50" disabled>
                                    Anterior
                                </button>

                                <span id="pagination-info">Página 1 de 1</span>

                                <button id="next-page" class="px-3 py-1 border rounded disabled:opacity-50">
                                    Próxima
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Script da página: responsável por buscar API, montar tabela, filtros, paginação, delete --}}
    <script type="module">
        // Referências dos elementos de DOM
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

        // Estado da paginação/filtros em memória no JS
        let currentPage = 1;
        const perPage = 10;
        let currentFilters = {
            month: '',
            user_id: '',
            category_id: '',
            type_id: '',
            payment_method_id: '',
        };

        /**
         * Função principal de carregamento da listagem.
         */
        async function loadTransactions(page = 1, filters = {}) {
            Alpine.$data(document.getElementById('transactions-table-wrapper')).loading = true;

            try {
                const params = new URLSearchParams();
                params.set('per_page', perPage);
                params.set('page', page);

                if (filters.month)              params.set('month', filters.month);
                if (filters.user_id)            params.set('user_id', filters.user_id);
                if (filters.category_id)        params.set('category_id', filters.category_id);
                if (filters.type_id)            params.set('type_id', filters.type_id);
                if (filters.payment_method_id)  params.set('payment_method_id', filters.payment_method_id);

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
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-4 text-sm font-semibold text-gray-900">Nenhuma transação encontrada</h3>
                            <p class="mt-1 text-sm text-gray-500">Tente ajustar os filtros ou adicione uma nova transação.</p>
                            <div class="mt-6">
                                <a href="{{ route('transactions.create') }}"
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
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

                // ===== RESUMO DA PÁGINA =====
                let totalIncome = 0;
                let totalExpense = 0;

                items.forEach(tx => {
                    const signed = Number(tx.signed_amount);
                    if (signed > 0) totalIncome += signed;
                    if (signed < 0) totalExpense += signed;
                });

                const balance = totalIncome + totalExpense;

                summaryEl.innerHTML = `
                    <div class="px-4 py-3 border rounded-lg bg-green-50">
                        <div class="text-xs text-gray-500">Receitas (página)</div>
                        <div class="text-lg font-semibold text-emerald-700">
                            ${totalIncome.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                    <div class="px-4 py-3 border rounded-lg bg-red-50">
                        <div class="text-xs text-gray-500">Despesas (página)</div>
                        <div class="text-lg font-semibold text-red-700">
                            ${totalExpense.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                    <div class="px-4 py-3 border rounded-lg bg-slate-50">
                        <div class="text-xs text-gray-500">Saldo (página)</div>
                        <div class="text-lg font-semibold ${balance >= 0 ? 'text-emerald-700' : 'text-red-700'}">
                            ${balance.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                `;

                // ===== TABELA =====
                items.forEach(tx => {
                    const tr = document.createElement('tr');

                    const isNegative = Number(tx.signed_amount) < 0;
                    const amountFormatted = Number(tx.signed_amount).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                    });

                    const totalAmount = tx.total_amount ?? null;
                    const totalFormatted = totalAmount !== null ?
                        Number(totalAmount).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                        }) :
                        null;

                    const perUserFormatted =
                        tx.totals &&
                        tx.totals.per_user_share !== null &&
                        tx.totals.per_user_share !== undefined ?
                        Number(tx.totals.per_user_share).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                        }) :
                        '-';

                    const ownerLabel = tx.credit_card?.owner_label || tx.credit_card?.owner_name || '';

                    const infoBadges = `
                        <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-slate-100 text-slate-700">
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
                            const share = Number(u.share_amount ?? 0).toLocaleString('pt-BR', {
                                style: 'currency',
                                currency: 'BRL',
                            });

                            return `
                                <div class="flex items-center justify-between gap-2">
                                    <span>${u.name}</span>
                                    <span class="text-xs text-gray-500">${share}</span>
                                </div>
                            `;
                        })
                        .join('');

                    const installmentLabel = tx.installments?.is_installment ?
                        `${tx.installments.label} · faltam ${tx.installments.remaining}` :
                        '-';

                    tr.innerHTML = `
                        <td class="px-3 py-2 text-gray-700 align-top">
                            <div class="flex flex-wrap gap-1">${infoBadges}</div>
                        </td>
                        <td class="px-3 py-2 text-gray-700 align-top">${tx.date}</td>
                        <td class="px-3 py-2 text-gray-700 align-top">
                            <div class="font-medium">${tx.description ?? '(sem descrição)'}</div>
                        </td>
                        <td class="px-3 py-2 text-right align-top">
                            <span class="${isNegative ? 'text-red-600' : 'text-emerald-600'} font-semibold">
                                ${amountFormatted}
                            </span>
                            ${
                                totalFormatted && tx.installments?.is_installment
                                    ? `<div class="text-xs text-gray-500">Total: ${totalFormatted}</div>`
                                    : ''
                            }
                        </td>
                        <td class="px-3 py-2 text-right text-gray-700 align-top">
                            ${perUserFormatted}
                        </td>
                        <td class="px-3 py-2 text-gray-700 align-top">
                            ${usersDetailHtml || '-'}
                        </td>
                        <td class="px-3 py-2 text-gray-700 align-top">
                            ${installmentLabel}
                        </td>
                        <td class="flex px-3 py-2 text-center align-top gap-1">
                            <a href="/transactions/${tx.id}/edit"
                               class="inline-flex items-center px-2 py-1 text-xs font-semibold text-blue-700 rounded hover:bg-blue-100">
                                ✏️
                            </a>
                            <button
                                data-id="${tx.id}"
                                class="delete-btn inline-flex items-center px-2 py-1 text-xs font-semibold text-red-700 rounded hover:bg-red-100">
                                🗑️
                            </button>
                        </td>
                    `;

                    bodyEl.appendChild(tr);
                });

                // ===== PAGINAÇÃO =====
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
                stateEl.textContent = 'Erro ao carregar transações.';
                bodyEl.innerHTML = '';
                summaryEl.innerHTML = '';
                paginationInfoEl.textContent = '';
                prevBtn.disabled = true;
                nextBtn.disabled = true;
            } finally {
                Alpine.$data(document.getElementById('transactions-table-wrapper')).loading = false;
            }
        }

        // Navegação de página anterior mantendo filtros
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) loadTransactions(currentPage - 1, currentFilters);
        });

        // Próxima página
        nextBtn.addEventListener('click', () => {
            loadTransactions(currentPage + 1, currentFilters);
        });

        // BOTÃO "Filtrar": atualiza currentFilters e recarrega página 1
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

        // BOTÃO "Limpar": reseta filtros e recarrega página 1
        filterClearBtn.addEventListener('click', () => {
            filterMonthEl.value = '';
            filterUserEl.value = '';
            filterCategoryEl.value = '';
            filterTypeEl.value = '';
            filterPaymentMethodEl.value = '';

            currentFilters = {
                month: '',
                user_id: '',
                category_id: '',
                type_id: '',
                payment_method_id: '',
            };

            loadTransactions(1, currentFilters);
        });

        // ======= DELETE (via modal centralizado + toast) =======
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

        // PRIMEIRA CARGA AO ABRIR A PÁGINA (sem filtros)
        loadTransactions(1, currentFilters);
    </script>

</x-app-layout>
