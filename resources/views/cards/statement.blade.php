<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">Extrato de Cartão</h1>
    </x-slot>

    <div class="space-y-6">

        {{-- Filtros --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-end">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Cartão</label>
                    <select id="filter-card"
                        class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @foreach ($cards as $card)
                            <option value="{{ $card->id }}" data-limit="{{ $card->limit ?? 0 }}">
                                {{ $card->name }}
                                @if ($card->owner)
                                    ({{ $card->owner->name }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Mês da fatura</label>
                    <input type="month" id="filter-month"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        value="{{ now()->format('Y-m') }}">
                </div>

                <button id="btn-load"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Carregar
                </button>
            </div>
        </div>

        {{-- Wrapper com loading skeleton + resultados --}}
        <div id="statement-wrapper" x-data="{ loading: false }">

            {{-- Skeleton --}}
            <div x-show="loading" class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="h-20 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="h-20 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="h-20 bg-slate-200 rounded-xl animate-pulse"></div>
                </div>
                <div class="h-8 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-8 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-8 bg-slate-200 rounded-xl animate-pulse"></div>
            </div>

            {{-- Conteúdo --}}
            <div x-show="!loading" class="space-y-4">

                <div id="card-state" class="text-sm text-slate-400">Escolha um cartão e um mês.</div>

                {{-- Cards de resumo --}}
                <div id="card-summary" class="grid grid-cols-1 gap-4 md:grid-cols-3 text-sm"></div>

                {{-- Barra de limite --}}
                <div id="limit-bar"
                    x-data="{
                        show: false,
                        limit: 0,
                        total: 0,
                        get percent() {
                            if (this.limit <= 0) return 0;
                            return Math.min((this.total / this.limit) * 100, 100).toFixed(1);
                        },
                        get barColor() {
                            const p = parseFloat(this.percent);
                            if (p >= 90) return 'bg-red-500';
                            if (p >= 70) return 'bg-amber-400';
                            return 'bg-emerald-500';
                        },
                        get textColor() {
                            const p = parseFloat(this.percent);
                            if (p >= 90) return 'text-red-600';
                            if (p >= 70) return 'text-amber-600';
                            return 'text-emerald-600';
                        }
                    }"
                    x-show="show"
                    style="display:none"
                    class="bg-white rounded-xl border border-slate-200 shadow-sm p-5"
                >
                    <p class="text-xs text-slate-500 mb-1">Limite utilizado</p>
                    <div class="flex items-end justify-between mb-2">
                        <span class="text-xl font-bold text-slate-900 tabular-nums">
                            R$ <span x-text="total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })"></span>
                        </span>
                        <span class="text-sm font-semibold tabular-nums" :class="textColor">
                            <span x-text="percent"></span>%
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div
                            class="h-2 rounded-full transition-all duration-500"
                            :class="barColor"
                            :style="`width: ${percent}%`"
                        ></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">
                        Limite total: R$ <span x-text="limit.toLocaleString('pt-BR', { minimumFractionDigits: 2 })"></span>
                    </p>
                </div>

                {{-- Badge/botão de status da fatura --}}
                <div id="card-status"></div>

                {{-- Período --}}
                <div id="card-period" class="text-xs text-slate-400"></div>

                {{-- Tabela --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Data</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoria</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody id="card-body" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script type="module">
        const cardSelect = document.getElementById('filter-card');
        const monthInput = document.getElementById('filter-month');
        const loadBtn = document.getElementById('btn-load');

        const stateEl = document.getElementById('card-state');
        const summaryEl = document.getElementById('card-summary');
        const statusEl = document.getElementById('card-status');
        const periodEl = document.getElementById('card-period');
        const bodyEl = document.getElementById('card-body');
        const limitBarEl = document.getElementById('limit-bar');

        async function loadStatement(cardId, year, month) {
            Alpine.$data(document.getElementById('statement-wrapper')).loading = true;

            try {
                const url = `/api/cards/${cardId}/statement?year=${year}&month=${month}`;
                const response = await fetch(url);

                if (!response.ok) throw new Error('Erro ao buscar fatura');

                const data = await response.json();

                if (!Array.isArray(data.transactions)) {
                    console.error('transactions não é um array:', data.transactions);
                    return;
                }

                stateEl.innerHTML = '';
                summaryEl.innerHTML = '';
                statusEl.innerHTML = '';
                periodEl.textContent = '';
                bodyEl.innerHTML = '';
                Alpine.$data(limitBarEl).show = false;

                summaryEl.innerHTML = `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Total da fatura</div>
                        <div class="text-xl font-bold text-red-600 tabular-nums">
                            R$ ${Math.abs(data.summary.expense).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Receitas no cartão</div>
                        <div class="text-xl font-bold text-emerald-700 tabular-nums">
                            R$ ${data.summary.income.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Saldo do cartão</div>
                        <div class="text-xl font-bold tabular-nums ${data.summary.net < 0 ? 'text-red-600' : 'text-emerald-700'}">
                            R$ ${data.summary.net.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </div>
                        <div class="text-xs text-slate-400 mt-1">Valor da fatura descontando pagamentos registrados</div>
                    </div>
                `;

                periodEl.textContent = `Período da fatura: ${data.period.start} até ${data.period.end}`;

                const statementId = data.meta?.statement_id;
                const statementStatus = data.meta?.status ?? 'open';

                if (statementId && statementStatus === 'closed') {
                    statusEl.innerHTML = `
                        <button id="btn-mark-paid"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Marcar como paga
                        </button>
                    `;
                    document.getElementById('btn-mark-paid').addEventListener('click', async () => {
                        try {
                            const res = await fetch(`/api/cards/statements/${statementId}/pay`, {
                                method: 'PATCH',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                                    'Accept': 'application/json',
                                },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) throw new Error();
                            statusEl.innerHTML = `
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-800 text-sm font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Fatura paga
                                </span>
                            `;
                        } catch {
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: 'Erro ao marcar fatura como paga.', type: 'error' }
                            }));
                        }
                    });
                } else if (statementStatus === 'paid') {
                    statusEl.innerHTML = `
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-800 text-sm font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Fatura paga
                        </span>
                    `;
                }

                const selectedOption = cardSelect.options[cardSelect.selectedIndex];
                const cardLimit = parseFloat(selectedOption?.dataset?.limit || 0);
                if (cardLimit > 0) {
                    const lb = Alpine.$data(limitBarEl);
                    lb.limit = cardLimit;
                    lb.total = Math.abs(data.summary.expense);
                    lb.show = true;
                }

                if (data.transactions.length === 0) {
                    bodyEl.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                                <p class="text-sm font-semibold text-slate-700">Fatura vazia</p>
                                <p class="text-xs text-slate-400 mt-1">Nenhuma transação encontrada neste período.</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                data.transactions.forEach(tx => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50';

                    tr.innerHTML = `
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap text-xs">${tx.date}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">${tx.description}</div>
                            ${
                                tx.installments.is_installment && tx.installments.label
                                    ? `<div class="text-xs text-slate-400">${tx.installments.label}</div>`
                                    : ''
                            }
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">${tx.category?.name ?? '-'}</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-800 tabular-nums">
                            R$ ${Number(tx.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </td>
                    `;

                    bodyEl.appendChild(tr);
                });

            } catch (error) {
                console.error('Erro em loadStatement:', error);
                stateEl.textContent = 'Erro ao carregar fatura. Tente novamente.';
            } finally {
                Alpine.$data(document.getElementById('statement-wrapper')).loading = false;
            }
        }

        function handleLoadClick() {
            const cardId = cardSelect.value;
            const monthValue = monthInput.value;
            if (!monthValue) { stateEl.textContent = 'Selecione um mês válido.'; return; }

            const [yearStr, monthStr] = monthValue.split('-');
            const year = parseInt(yearStr, 10);
            const month = parseInt(monthStr, 10);

            if (!cardId || isNaN(year) || isNaN(month)) { stateEl.textContent = 'Filtro inválido.'; return; }

            loadStatement(cardId, year, month);
        }

        loadBtn.addEventListener('click', handleLoadClick);
    </script>
</x-app-layout>
