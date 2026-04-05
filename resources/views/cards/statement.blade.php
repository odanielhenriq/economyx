<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Extrato de Cartão
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">

                    {{-- Filtros do extrato de cartão: cartão + mês da fatura --}}
                    <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-end">
                        <div class="flex-1">
                            <label class="text-sm text-gray-600">Cartão</label>
                            <select id="filter-card" class="block w-full mt-1 text-sm border-gray-300 rounded">
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
                            <label class="text-sm text-gray-600">Mês da fatura</label>
                            <input type="month" id="filter-month"
                                class="block w-full mt-1 text-sm border-gray-300 rounded"
                                value="{{ now()->format('Y-m') }}">
                        </div>

                        <div class="flex gap-2">
                            <button id="btn-load"
                                class="px-4 py-2 mt-4 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                Carregar
                            </button>
                        </div>
                    </div>

                    {{-- Wrapper com loading skeleton + resultados --}}
                    <div id="statement-wrapper" x-data="{ loading: false }">

                        {{-- Skeleton: visível durante o carregamento --}}
                        <div x-show="loading" class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="h-16 bg-gray-200 rounded animate-pulse"></div>
                                <div class="h-16 bg-gray-200 rounded animate-pulse"></div>
                                <div class="h-16 bg-gray-200 rounded animate-pulse"></div>
                            </div>
                            <div class="h-8 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-8 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-8 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-8 bg-gray-200 rounded animate-pulse"></div>
                        </div>

                        {{-- Conteúdo: mensagem inicial / resultados / empty state --}}
                        <div x-show="!loading">

                            {{-- Mensagem de estado (inicial, vazio, erro) --}}
                            <div id="card-state" class="text-sm text-gray-500">Escolha um cartão e um mês.</div>

                            {{-- Resumo da fatura (cards com total, receitas, líquido) --}}
                            <div id="card-summary" class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-3 text-sm">
                                {{-- preenchido via JS --}}
                            </div>

                            {{-- Barra de limite do cartão --}}
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
                                class="mt-4 p-4 border rounded-lg bg-gray-50"
                            >
                                <p class="text-xs text-gray-500 mb-1">Limite utilizado</p>
                                <div class="flex items-end justify-between mb-2">
                                    <span class="text-xl font-bold text-gray-800">
                                        R$ <span x-text="total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })"></span>
                                    </span>
                                    <span class="text-sm font-semibold" :class="textColor">
                                        <span x-text="percent"></span>%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div
                                        class="h-2.5 rounded-full transition-all duration-500"
                                        :class="barColor"
                                        :style="`width: ${percent}%`"
                                    ></div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    Limite total: R$ <span x-text="limit.toLocaleString('pt-BR', { minimumFractionDigits: 2 })"></span>
                                </p>
                            </div>

                            {{-- Badge/botão de status da fatura --}}
                            <div id="card-status" class="mt-3"></div>

                            {{-- Texto com período real de cobrança da fatura --}}
                            <div id="card-period" class="mt-2 text-xs text-gray-500">
                                {{-- preenchido via JS --}}
                            </div>

                            {{-- Tabela das transações daquela fatura de cartão --}}
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm text-left">
                                    <thead class="border-b text-gray-600">
                                        <tr>
                                            <th class="px-3 py-2">Data</th>
                                            <th class="px-3 py-2">Descrição</th>
                                            <th class="px-3 py-2">Categoria</th>
                                            <th class="px-3 py-2 text-right">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody id="card-body" class="divide-y">
                                        {{-- linhas via JS (dados da CardStatementController@statement) --}}
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Script que consome /api/cards/{card}/statement e renderiza tudo --}}
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

        /**
         * Busca o extrato da API e renderiza resumo + tabela.
         */
        async function loadStatement(cardId, year, month) {
            Alpine.$data(document.getElementById('statement-wrapper')).loading = true;

            try {
                const url = `/api/cards/${cardId}/statement?year=${year}&month=${month}`;
                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error('Erro ao buscar fatura');
                }

                const data = await response.json();

                if (!Array.isArray(data.transactions)) {
                    console.error('transactions não é um array:', data.transactions);
                    return;
                }

                // Limpa estado anterior
                stateEl.innerHTML = '';
                summaryEl.innerHTML = '';
                statusEl.innerHTML = '';
                periodEl.textContent = '';
                bodyEl.innerHTML = '';
                Alpine.$data(limitBarEl).show = false;

                // Monta os 3 cards de resumo
                summaryEl.innerHTML = `
                    <div class="p-3 border rounded">
                        <div class="text-xs text-gray-500">Total da fatura</div>
                        <div class="text-lg font-semibold text-red-600">
                            R$ ${Math.abs(data.summary.expense).toFixed(2)}
                        </div>
                    </div>
                    <div class="p-3 border rounded">
                        <div class="text-xs text-gray-500">Receitas no cartão</div>
                        <div class="text-lg font-semibold text-green-600">
                            R$ ${data.summary.income.toFixed(2)}
                        </div>
                    </div>
                    <div class="p-3 border rounded">
                        <div class="text-xs text-gray-500">Líquido</div>
                        <div class="text-lg font-semibold ${data.summary.net < 0 ? 'text-red-600' : 'text-green-600'}">
                            R$ ${data.summary.net.toFixed(2)}
                        </div>
                    </div>
                `;

                periodEl.textContent = `Período da fatura: ${data.period.start} até ${data.period.end}`;

                // Badge / botão de status da fatura
                const statementId = data.meta?.statement_id;
                const statementStatus = data.meta?.status ?? 'open';

                if (statementId && statementStatus === 'closed') {
                    statusEl.innerHTML = `
                        <button id="btn-mark-paid"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white
                                   text-sm font-medium rounded-md hover:bg-green-700 transition">
                            ✓ Marcar como paga
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
                                <span class="inline-flex items-center px-3 py-1 rounded-full
                                             bg-green-100 text-green-800 text-sm font-medium">
                                    ✓ Fatura paga
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
                        <span class="inline-flex items-center px-3 py-1 rounded-full
                                     bg-green-100 text-green-800 text-sm font-medium">
                            ✓ Fatura paga
                        </span>
                    `;
                }

                // Barra de limite: lê o limite do data-limit da option selecionada
                const selectedOption = cardSelect.options[cardSelect.selectedIndex];
                const cardLimit = parseFloat(selectedOption?.dataset?.limit || 0);
                if (cardLimit > 0) {
                    const lb = Alpine.$data(limitBarEl);
                    lb.limit = cardLimit;
                    lb.total = Math.abs(data.summary.expense);
                    lb.show = true;
                }

                if (data.transactions.length === 0) {
                    stateEl.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <h3 class="mt-4 text-sm font-semibold text-gray-900">Fatura vazia</h3>
                            <p class="mt-1 text-sm text-gray-500">Nenhuma transação encontrada neste período de fatura.</p>
                        </div>
                    `;
                    return;
                }

                // Monta cada linha da tabela
                data.transactions.forEach(tx => {
                    const tr = document.createElement('tr');

                    tr.innerHTML = `
                        <td class="px-3 py-2 text-xs text-gray-500">${tx.date}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium">${tx.description}</div>
                            ${
                                tx.installments.is_installment && tx.installments.label
                                    ? `<div class="text-xs text-gray-500">${tx.installments.label}</div>`
                                    : ''
                            }
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">
                            ${tx.category?.name ?? '-'}
                        </td>
                        <td class="px-3 py-2 text-right">
                            R$ ${Number(tx.amount).toFixed(2)}
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

        // Lê valores dos filtros, valida e chama loadStatement
        function handleLoadClick() {
            const cardId = cardSelect.value;

            const monthValue = monthInput.value;
            if (!monthValue) {
                stateEl.textContent = 'Selecione um mês válido.';
                return;
            }

            const [yearStr, monthStr] = monthValue.split('-');
            const year = parseInt(yearStr, 10);
            const month = parseInt(monthStr, 10);

            if (!cardId || isNaN(year) || isNaN(month)) {
                stateEl.textContent = 'Filtro inválido.';
                return;
            }

            loadStatement(cardId, year, month);
        }

        loadBtn.addEventListener('click', handleLoadClick);
    </script>
</x-app-layout>
