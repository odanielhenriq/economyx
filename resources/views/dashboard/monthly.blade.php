<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">Dashboard</h1>
    </x-slot>

    <div id="dashboard-wrapper" x-data="{ loading: true }">
        <div class="space-y-6">

            {{-- Navegador de mês --}}
            <div
                x-data="{
                    year: {{ $year }},
                    month: {{ $month }},
                    get label() {
                        return new Date(this.year, this.month - 1)
                            .toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
                    },
                    prev() {
                        if (this.month === 1) { this.month = 12; this.year--; }
                        else { this.month--; }
                        this.navigate();
                    },
                    next() {
                        if (this.month === 12) { this.month = 1; this.year++; }
                        else { this.month++; }
                        this.navigate();
                    },
                    navigate() {
                        window.location.href = `?year=${this.year}&month=${this.month}`;
                    }
                }"
                class="flex items-center justify-center gap-6"
            >
                <button @click="prev()"
                    class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition">
                    ←
                </button>
                <span x-text="label" class="text-lg font-semibold text-slate-800 capitalize w-52 text-center"></span>
                <button @click="next()"
                    class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition">
                    →
                </button>
            </div>

            {{-- Skeleton: visível enquanto carrega --}}
            <div x-show="loading" class="space-y-4">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="h-24 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="h-24 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="h-24 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="h-24 bg-slate-200 rounded-xl animate-pulse"></div>
                </div>
                <div class="h-44 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="h-44 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="h-44 bg-slate-200 rounded-xl animate-pulse"></div>
                </div>
                <div class="h-64 bg-slate-200 rounded-xl animate-pulse"></div>
            </div>

            {{-- Conteúdo real: visível após carregar --}}
            <div x-show="!loading" style="display:none" class="space-y-6">

                {{-- Cards de resumo --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Receitas --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500">Receitas do mês</span>
                            <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                                </svg>
                            </div>
                        </div>
                        <p id="dashboard-income" class="text-2xl font-bold text-slate-900 tabular-nums"></p>
                    </div>

                    {{-- Despesas --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500">Despesas do mês</span>
                            <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.306-4.307a11.95 11.95 0 015.814 5.519l2.74 1.22m0 0l-5.94 2.28m5.94-2.28l-2.28-5.941" />
                                </svg>
                            </div>
                        </div>
                        <p id="dashboard-expense" class="text-2xl font-bold text-slate-900 tabular-nums"></p>
                    </div>

                    {{-- Saldo --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500">Saldo do mês</span>
                            <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 5.277a1.125 1.125 0 01-.12 1.06 48.516 48.516 0 01-7.5 0 1.125 1.125 0 01-.12-1.06l2.62-5.277m0 0A48.416 48.416 0 0112 4.5" />
                                </svg>
                            </div>
                        </div>
                        <p id="dashboard-balance" class="text-2xl font-bold tabular-nums"></p>
                    </div>

                    {{-- A pagar --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500">A pagar no mês</span>
                            <div class="w-9 h-9 bg-slate-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                            </div>
                        </div>
                        <p id="dashboard-payable-total" class="text-2xl font-bold text-slate-900 tabular-nums"></p>
                        <p id="dashboard-payable-breakdown" class="mt-1 text-xs text-slate-500 tabular-nums"></p>
                    </div>
                </div>

                {{-- Alertas de orçamento por categoria --}}
                @if (count($budgetAlerts) > 0)
                    <div class="space-y-2">
                        @foreach ($budgetAlerts as $alert)
                            <div class="flex items-center gap-4 p-3 rounded-xl border
                                {{ $alert['over'] ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' }}">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium
                                            {{ $alert['over'] ? 'text-red-800' : 'text-amber-800' }}">
                                            {{ $alert['over'] ? '⚠ Limite ultrapassado' : '⚡ Próximo do limite' }}
                                            — {{ $alert['category'] }}
                                        </span>
                                        <span class="text-xs font-semibold tabular-nums
                                            {{ $alert['over'] ? 'text-red-700' : 'text-amber-700' }}">
                                            {{ $alert['percent'] }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full transition-all duration-500
                                            {{ $alert['over'] ? 'bg-red-500' : 'bg-amber-400' }}"
                                            style="width: {{ min($alert['percent'], 100) }}%">
                                        </div>
                                    </div>
                                    <div class="mt-1 text-xs tabular-nums {{ $alert['over'] ? 'text-red-600' : 'text-amber-600' }}">
                                        R$ {{ number_format($alert['spent'], 2, ',', '.') }}
                                        de R$ {{ number_format($alert['limit'], 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Gráfico Receita × Despesa — últimos 6 meses --}}
                <script>window._chartData = @json($chartData);</script>
                <div
                    x-data="{
                        months: window._chartData,
                        get maxVal() {
                            return Math.max(...this.months.flatMap(m => [m.income, m.expense]), 1);
                        },
                        barHeight(val) {
                            return Math.round((val / this.maxVal) * 120) + 'px';
                        }
                    }"
                    class="bg-white rounded-xl border border-slate-200 shadow-sm"
                >
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 rounded-t-xl flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Receita × Despesa — últimos 6 meses</h3>
                        <div class="flex gap-4">
                            <span class="flex items-center gap-1 text-xs text-slate-500">
                                <span class="inline-block w-3 h-3 rounded-sm bg-emerald-500"></span> Receita
                            </span>
                            <span class="flex items-center gap-1 text-xs text-slate-500">
                                <span class="inline-block w-3 h-3 rounded-sm bg-red-400"></span> Despesa
                            </span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div x-show="months.every(m => m.income === 0 && m.expense === 0)"
                             class="py-8 text-xs text-center text-slate-400">
                            Sem movimentações nos últimos 6 meses.
                        </div>
                        <div x-show="months.some(m => m.income > 0 || m.expense > 0)"
                             class="flex items-end justify-around gap-2" style="height: 140px">
                            <template x-for="m in months" :key="m.label">
                                <div class="flex flex-col items-center flex-1 min-w-0">
                                    <div class="flex items-end justify-center gap-0.5" style="height: 120px">
                                        <div
                                            class="w-4 rounded-t bg-emerald-500 transition-all duration-500"
                                            :style="'height: ' + barHeight(m.income)"
                                            :title="'Receita: R$ ' + m.income.toLocaleString('pt-BR', { minimumFractionDigits: 2 })"
                                        ></div>
                                        <div
                                            class="w-4 rounded-t bg-red-400 transition-all duration-500"
                                            :style="'height: ' + barHeight(m.expense)"
                                            :title="'Despesa: R$ ' + m.expense.toLocaleString('pt-BR', { minimumFractionDigits: 2 })"
                                        ></div>
                                    </div>
                                    <span class="mt-1 text-[10px] text-slate-400 text-center leading-tight"
                                          x-text="m.label"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- A pagar no mês --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-200">
                            <h3 class="text-sm font-semibold text-slate-700">A pagar no mês — Cartões</h3>
                        </div>
                        <div id="payables-cards-list" class="p-5 space-y-3 text-sm"></div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-200">
                            <h3 class="text-sm font-semibold text-slate-700">A pagar no mês — Parcelas de compras</h3>
                        </div>
                        <div id="payables-loans-list" class="p-5 space-y-3 text-sm"></div>
                    </div>
                </div>

                {{-- Movimentações do mês --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="text-sm font-semibold text-slate-700">Movimentações do mês</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Transações à vista e parcelas com vencimento neste mês</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vencimento</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cartão</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Valor</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="cashflow-body" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script type="module">
        const dashboardYear = @json($year);
        const dashboardMonth = @json($month);

        const incomeEl = document.getElementById('dashboard-income');
        const expenseEl = document.getElementById('dashboard-expense');
        const balanceEl = document.getElementById('dashboard-balance');
        const payableTotalEl = document.getElementById('dashboard-payable-total');
        const payableBreakdownEl = document.getElementById('dashboard-payable-breakdown');
        const payablesCardsListEl = document.getElementById('payables-cards-list');
        const payablesLoansListEl = document.getElementById('payables-loans-list');
        const cashflowBodyEl = document.getElementById('cashflow-body');

        const moneyFormatter = new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const formatMoney = (value) => {
            const amount = Number(value ?? 0);
            const safeAmount = Number.isFinite(amount) ? amount : 0;
            return `R$ ${moneyFormatter.format(safeAmount)}`;
        };

        const renderCards = (cards = {}) => {
            incomeEl.textContent = formatMoney(cards.income_total_month);
            expenseEl.textContent = formatMoney(cards.expense_total_month);

            const balance = Number(cards.balance_month ?? 0);
            balanceEl.textContent = formatMoney(balance);
            balanceEl.classList.remove('text-emerald-700', 'text-red-600', 'text-slate-500');
            balanceEl.classList.add(balance < 0 ? 'text-red-600' : 'text-emerald-700');

            payableTotalEl.textContent = formatMoney(cards.payable_total_month);

            const breakdown = cards.breakdown ?? {};
            const cardsTotal = breakdown.payable_cards_total ?? 0;
            const loansTotal = breakdown.payable_loans_total ?? 0;
            payableBreakdownEl.textContent =
                `Cartões: ${formatMoney(cardsTotal)} · Empréstimos: ${formatMoney(loansTotal)}`;
        };

        const renderPayablesCards = (list = []) => {
            payablesCardsListEl.innerHTML = '';

            if (!list.length) {
                payablesCardsListEl.innerHTML = '<div class="text-xs text-slate-400">Nenhuma fatura encontrada para este mês.</div>';
                return;
            }

            list.forEach((card) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center justify-between gap-3';

                const info = document.createElement('div');

                const title = document.createElement('div');
                title.className = 'font-medium text-slate-800';
                title.textContent = card?.card_name ?? 'Cartão';

                if (card?.owner_name) {
                    const owner = document.createElement('span');
                    owner.className = 'text-xs text-slate-400';
                    owner.textContent = `(${card.owner_name})`;
                    title.appendChild(document.createTextNode(' '));
                    title.appendChild(owner);
                }

                const due = document.createElement('div');
                due.className = 'text-xs text-slate-400';
                due.textContent = `Vencimento: ${card?.due_date ?? '-'}`;

                info.appendChild(title);
                info.appendChild(due);

                const amount = document.createElement('div');
                amount.className = 'font-semibold text-red-600 tabular-nums';
                amount.textContent = formatMoney(card?.total ?? 0);

                wrapper.appendChild(info);
                wrapper.appendChild(amount);

                payablesCardsListEl.appendChild(wrapper);
            });
        };

        const renderPayablesLoans = (list = []) => {
            payablesLoansListEl.innerHTML = '';

            if (!list.length) {
                payablesLoansListEl.innerHTML = '<div class="text-xs text-slate-400">Nenhuma parcela de empréstimo neste mês.</div>';
                return;
            }

            list.forEach((loan) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center justify-between gap-3';

                const info = document.createElement('div');

                const title = document.createElement('div');
                title.className = 'font-medium text-slate-800';
                title.textContent = loan?.description ?? 'Parcela';

                const due = document.createElement('div');
                due.className = 'text-xs text-slate-400';

                const dueText = `Vencimento: ${loan?.due_date ?? '-'}`;
                const totalInstallments = Number(loan?.installment_total ?? 0);
                const installmentNumber = loan?.installment_number ?? null;

                if (totalInstallments > 1 && installmentNumber) {
                    due.textContent = `${dueText} · ${installmentNumber}/${totalInstallments}`;
                } else {
                    due.textContent = dueText;
                }

                info.appendChild(title);
                info.appendChild(due);

                const amount = document.createElement('div');
                amount.className = 'font-semibold text-red-600 tabular-nums';
                amount.textContent = formatMoney(loan?.amount ?? 0);

                wrapper.appendChild(info);
                wrapper.appendChild(amount);

                payablesLoansListEl.appendChild(wrapper);
            });
        };

        const renderCashflow = (items = []) => {
            cashflowBodyEl.innerHTML = '';

            if (!items.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 5;
                cell.className = 'px-4 py-4 text-xs text-slate-400';
                cell.textContent = 'Nenhuma movimentação encontrada.';
                row.appendChild(cell);
                cashflowBodyEl.appendChild(row);
                return;
            }

            items.forEach((item) => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-slate-50';

                // Vencimento
                const dueCell = document.createElement('td');
                dueCell.className = 'px-4 py-3 text-slate-500 whitespace-nowrap';
                if (item?.due_date) {
                    const date = new Date(item.due_date);
                    dueCell.textContent = date.toLocaleDateString('pt-BR');
                } else {
                    dueCell.textContent = '-';
                }

                // Descrição
                const descCell = document.createElement('td');
                descCell.className = 'px-4 py-3 text-slate-800';
                descCell.textContent = item?.description ?? '-';

                // Cartão
                const cardCell = document.createElement('td');
                cardCell.className = 'px-4 py-3';
                if (item?.credit_card_name) {
                    const cardBadge = document.createElement('span');
                    cardBadge.className = 'inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-full bg-blue-100 text-blue-700';
                    cardBadge.textContent = item.credit_card_name;
                    cardCell.appendChild(cardBadge);
                } else {
                    cardCell.textContent = '-';
                    cardCell.className += ' text-slate-400';
                }

                // Valor
                const amountCell = document.createElement('td');
                amountCell.className = 'px-4 py-3 text-right font-medium tabular-nums';
                const amount = Number(item?.amount ?? 0);
                amountCell.textContent = formatMoney(amount);

                const typeSlug = item?.type_slug ?? '';
                if (typeSlug === 'rc') {
                    amountCell.classList.add('text-emerald-700');
                } else if (typeSlug === 'dc') {
                    amountCell.classList.add('text-red-600');
                } else {
                    amountCell.classList.add(amount < 0 ? 'text-red-600' : 'text-emerald-700');
                }

                // Status
                const statusCell = document.createElement('td');
                statusCell.className = 'px-4 py-3 text-center';

                const isProjection = (item?.source ?? '') === 'projection';
                const isInstallment = (item?.source ?? '') === 'installment';
                const isSpot = item?.is_spot ?? false;
                const badge = document.createElement('span');

                if (isProjection) {
                    badge.className = 'inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-amber-100 text-amber-700';
                    badge.textContent = 'Previsto';
                } else if (isInstallment) {
                    badge.className = 'inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-purple-100 text-purple-700';
                    badge.textContent = 'Parcela';
                } else if (isSpot) {
                    badge.className = 'inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-blue-100 text-blue-700';
                    badge.textContent = 'À vista';
                } else {
                    badge.className = 'inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-emerald-100 text-emerald-700';
                    badge.textContent = 'Registrado';
                }

                statusCell.appendChild(badge);

                row.appendChild(dueCell);
                row.appendChild(descCell);
                row.appendChild(cardCell);
                row.appendChild(amountCell);
                row.appendChild(statusCell);

                cashflowBodyEl.appendChild(row);
            });
        };

        const renderError = () => {
            incomeEl.textContent = '-';
            expenseEl.textContent = '-';
            balanceEl.textContent = '-';
            balanceEl.classList.remove('text-emerald-700', 'text-red-600');
            balanceEl.classList.add('text-slate-500');
            payableTotalEl.textContent = '-';
            payableBreakdownEl.textContent = 'Erro ao carregar dados do dashboard.';

            payablesCardsListEl.innerHTML = '<div class="text-xs text-slate-400">Erro ao carregar cartões.</div>';
            payablesLoansListEl.innerHTML = '<div class="text-xs text-slate-400">Erro ao carregar empréstimos.</div>';

            cashflowBodyEl.innerHTML = '';
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.className = 'px-4 py-4 text-xs text-slate-400';
            cell.textContent = 'Erro ao carregar movimentações.';
            row.appendChild(cell);
            cashflowBodyEl.appendChild(row);
        };

        const loadDashboard = async () => {
            const url = new URL('/api/dashboard/monthly', window.location.origin);
            url.searchParams.set('year', dashboardYear);
            url.searchParams.set('month', dashboardMonth);

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`Erro na API: ${response.status}`);
                }

                const data = await response.json();

                renderCards(data?.cards ?? {});
                renderPayablesCards(data?.lists?.payables_cards ?? []);
                renderPayablesLoans(data?.lists?.payables_loans ?? []);
                renderCashflow(data?.lists?.cashflow_items ?? []);
            } catch (error) {
                console.error(error);
                renderError();
            } finally {
                Alpine.$data(document.getElementById('dashboard-wrapper')).loading = false;
            }
        };

        loadDashboard();
    </script>
</x-app-layout>
