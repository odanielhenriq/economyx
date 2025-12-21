<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard mensal</h2>
                <p class="text-sm text-gray-500">Competência: {{ $monthLabel }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-3">
                <a href="{{ route('dashboard.monthly', ['year' => $prev['year'], 'month' => $prev['month']]) }}"
                    class="inline-flex items-center px-3 py-2 text-xs border rounded text-gray-700 hover:bg-gray-50">
                    ← Mês anterior
                </a>
                <form method="GET" action="{{ route('dashboard.monthly') }}" class="flex items-end gap-2">
                    <div>
                        <label class="text-xs text-gray-500">Mês</label>
                        <select name="month" class="block w-full mt-1 text-sm border-gray-300 rounded">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($m == $month)>
                                    {{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Ano</label>
                        <input type="number" name="year" value="{{ $year }}" min="2000" max="2100"
                            class="block w-24 mt-1 text-sm border-gray-300 rounded">
                    </div>
                    <button type="submit"
                        class="px-3 py-2 text-xs text-white bg-indigo-600 rounded hover:bg-indigo-700">
                        Ir
                    </button>
                </form>
                <a href="{{ route('dashboard.monthly', ['year' => $next['year'], 'month' => $next['month']]) }}"
                    class="inline-flex items-center px-3 py-2 text-xs border rounded text-gray-700 hover:bg-gray-50">
                    Próximo mês →
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8 space-y-8">
            {{-- Cards de resumo --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">Receitas do mês</div>
                    <div id="dashboard-income" class="text-lg font-semibold text-emerald-700">Carregando...</div>
                </div>
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">Despesas do mês</div>
                    <div id="dashboard-expense" class="text-lg font-semibold text-red-600">Carregando...</div>
                </div>
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">Saldo do mês</div>
                    <div id="dashboard-balance" class="text-lg font-semibold text-emerald-700">Carregando...</div>
                </div>
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">A pagar no mês</div>
                    <div id="dashboard-payable-total" class="text-lg font-semibold text-gray-800">
                        Carregando...
                    </div>
                    <div id="dashboard-payable-breakdown" class="mt-1 text-xs text-gray-500">
                        Carregando...
                    </div>
                </div>
            </div>

            {{-- A pagar no mês --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="bg-white border rounded shadow-sm">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">A pagar no mês — Cartões</h3>
                    </div>
                    <div id="payables-cards-list" class="p-4 space-y-3 text-sm">
                        <div class="text-xs text-gray-500">Carregando faturas...</div>
                    </div>
                </div>

                <div class="bg-white border rounded shadow-sm">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">A pagar no mês — Empréstimos</h3>
                    </div>
                    <div id="payables-loans-list" class="p-4 space-y-3 text-sm">
                        <div class="text-xs text-gray-500">Carregando parcelas...</div>
                    </div>
                </div>
            </div>

            {{-- Movimentações do mês --}}
            <div class="bg-white border rounded shadow-sm">
                <div class="px-4 py-3 border-b">
                    <h3 class="text-sm font-semibold text-gray-700">Movimentações do mês</h3>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="border-b text-gray-600">
                            <tr>
                                <th class="px-3 py-2">Vencimento</th>
                                <th class="px-3 py-2">Descrição</th>
                                <th class="px-3 py-2 text-right">Valor</th>
                                <th class="px-3 py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="cashflow-body" class="divide-y">
                            <tr>
                                <td colspan="4" class="px-3 py-4 text-xs text-gray-500">
                                    Carregando movimentações...
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
            balanceEl.classList.remove('text-emerald-700', 'text-red-600', 'text-gray-500');
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
                const empty = document.createElement('div');
                empty.className = 'text-xs text-gray-500';
                empty.textContent = 'Nenhuma fatura encontrada para este mês.';
                payablesCardsListEl.appendChild(empty);
                return;
            }

            list.forEach((card) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center justify-between gap-3';

                const info = document.createElement('div');

                const title = document.createElement('div');
                title.className = 'font-medium text-gray-800';
                title.textContent = card?.card_name ?? 'Cartão';

                if (card?.owner_name) {
                    const owner = document.createElement('span');
                    owner.className = 'text-xs text-gray-500';
                    owner.textContent = `(${card.owner_name})`;
                    title.appendChild(document.createTextNode(' '));
                    title.appendChild(owner);
                }

                const due = document.createElement('div');
                due.className = 'text-xs text-gray-500';
                due.textContent = `Vencimento: ${card?.due_date ?? '-'}`;

                info.appendChild(title);
                info.appendChild(due);

                const amount = document.createElement('div');
                amount.className = 'font-semibold text-red-600';
                amount.textContent = formatMoney(card?.total ?? 0);

                wrapper.appendChild(info);
                wrapper.appendChild(amount);

                payablesCardsListEl.appendChild(wrapper);
            });
        };

        const renderPayablesLoans = (list = []) => {
            payablesLoansListEl.innerHTML = '';

            if (!list.length) {
                const empty = document.createElement('div');
                empty.className = 'text-xs text-gray-500';
                empty.textContent = 'Nenhuma parcela de empréstimo neste mês.';
                payablesLoansListEl.appendChild(empty);
                return;
            }

            list.forEach((loan) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center justify-between gap-3';

                const info = document.createElement('div');

                const title = document.createElement('div');
                title.className = 'font-medium text-gray-800';
                title.textContent = loan?.description ?? 'Parcela';

                const due = document.createElement('div');
                due.className = 'text-xs text-gray-500';

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
                amount.className = 'font-semibold text-red-600';
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
                cell.colSpan = 4;
                cell.className = 'px-3 py-4 text-xs text-gray-500';
                cell.textContent = 'Nenhuma movimentação encontrada.';
                row.appendChild(cell);
                cashflowBodyEl.appendChild(row);
                return;
            }

            items.forEach((item) => {
                const row = document.createElement('tr');

                const dueCell = document.createElement('td');
                dueCell.className = 'px-3 py-2 text-gray-600';
                dueCell.textContent = item?.due_date ?? '-';

                const descCell = document.createElement('td');
                descCell.className = 'px-3 py-2 text-gray-800';
                descCell.textContent = item?.description ?? '-';

                const amountCell = document.createElement('td');
                amountCell.className = 'px-3 py-2 text-right';
                amountCell.textContent = formatMoney(item?.amount ?? 0);

                const statusCell = document.createElement('td');
                statusCell.className = 'px-3 py-2 text-center';

                const isProjection = (item?.source ?? '') === 'projection';
                const badge = document.createElement('span');
                badge.className =
                    `inline-flex items-center px-2 py-0.5 text-[11px] rounded-full ${isProjection ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`;
                badge.textContent = isProjection ? 'Previsto' : 'Real';

                statusCell.appendChild(badge);

                row.appendChild(dueCell);
                row.appendChild(descCell);
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
            balanceEl.classList.add('text-gray-500');
            payableTotalEl.textContent = '-';
            payableBreakdownEl.textContent = 'Erro ao carregar dados do dashboard.';

            payablesCardsListEl.innerHTML = '';
            payablesLoansListEl.innerHTML = '';
            cashflowBodyEl.innerHTML = '';

            const cardsError = document.createElement('div');
            cardsError.className = 'text-xs text-gray-500';
            cardsError.textContent = 'Erro ao carregar cartões.';
            payablesCardsListEl.appendChild(cardsError);

            const loansError = document.createElement('div');
            loansError.className = 'text-xs text-gray-500';
            loansError.textContent = 'Erro ao carregar empréstimos.';
            payablesLoansListEl.appendChild(loansError);

            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'px-3 py-4 text-xs text-gray-500';
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
            }
        };

        loadDashboard();
    </script>
</x-app-layout>
