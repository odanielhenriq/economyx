<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Dashboard"
            subtitle="Resumo do mês selecionado: quanto entrou, quanto saiu, o que vence e onde você gastou."
        />
    </x-slot>

    <div id="dashboard-wrapper" x-data="{ loading: true }">
        <div class="space-y-6">

            <x-first-use-checklist
                :has-transactions="$hasTransactions"
                :has-credit-cards="$hasCreditCards"
                :has-budgets="$hasBudgets"
            />

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
                <button @click="prev()" aria-label="Mês anterior"
                    class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition">
                    ←
                </button>
                <span x-text="label" class="text-lg font-semibold text-slate-800 capitalize w-52 text-center" aria-live="polite"></span>
                <button @click="next()" aria-label="Próximo mês"
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
                <div class="h-28 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-44 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-40 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="h-44 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="h-44 bg-slate-200 rounded-xl animate-pulse"></div>
                </div>
                <div class="h-64 bg-slate-200 rounded-xl animate-pulse"></div>
            </div>

            {{-- Conteúdo real: visível após carregar --}}
            <div x-show="!loading" style="display:none" class="space-y-6">

                {{-- Variação mês-a-mês (calculada a partir do histórico PHP) --}}
                @php
                    $curIncome  = $chartData[5]['income']  ?? 0;
                    $prevIncome = $previousMonthData['income']  ?? 0;
                    $curExpense = $chartData[5]['expense'] ?? 0;
                    $prevExpense = $previousMonthData['expense'] ?? 0;

                    $incomeVar  = $prevIncome  > 0 ? (($curIncome  - $prevIncome)  / $prevIncome)  * 100 : null;
                    $expenseVar = $prevExpense > 0 ? (($curExpense - $prevExpense) / $prevExpense) * 100 : null;
                @endphp

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
                        <p id="dashboard-income" class="text-2xl sm:text-3xl font-bold text-emerald-700 tabular-nums"></p>
                        <p class="text-xs text-slate-400 mt-1">Salários, recebimentos e entradas registradas no mês</p>
                        @if($incomeVar !== null)
                            <p class="text-xs mt-1 font-medium {{ $incomeVar >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $incomeVar >= 0 ? '↑' : '↓' }} {{ number_format(abs($incomeVar), 1) }}% vs mês passado
                            </p>
                        @endif
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
                        <p id="dashboard-expense" class="text-2xl sm:text-3xl font-bold text-red-600 tabular-nums"></p>
                        <p class="text-xs text-slate-400 mt-1">Despesas à vista e contas fixas do mês (sem faturas de cartão)</p>
                        @if($expenseVar !== null)
                            {{-- Despesa subindo = ruim (vermelho), descendo = bom (verde) --}}
                            <p class="text-xs mt-1 font-medium {{ $expenseVar > 0 ? 'text-red-500' : 'text-green-600' }}">
                                {{ $expenseVar > 0 ? '↑' : '↓' }} {{ number_format(abs($expenseVar), 1) }}% vs mês passado
                            </p>
                        @endif
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
                        <p id="dashboard-balance" class="text-2xl sm:text-3xl font-bold tabular-nums"></p>
                        <p class="text-xs text-slate-400 mt-1">Receitas menos despesas do mês</p>
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
                        <p id="dashboard-payable-total" class="text-2xl sm:text-3xl font-bold text-slate-900 tabular-nums"></p>
                        <p id="dashboard-payable-breakdown" class="mt-1 text-xs text-slate-500 tabular-nums"></p>
                        <p class="text-xs text-slate-400 mt-1">Faturas de cartão e parcelas de empréstimos com vencimento neste mês</p>
                    </div>
                </div>

                {{-- Saldo projetado --}}
                <div id="projected-balance-card"
                     class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm font-medium text-slate-500">Saldo projetado</span>
                                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                </div>
                            </div>
                            <p id="dashboard-projected-balance"
                               class="text-2xl sm:text-3xl font-bold tabular-nums text-slate-900"></p>
                            <p id="dashboard-projected-hint" class="text-xs text-slate-500 mt-1"></p>
                            <p class="text-xs text-slate-400 mt-2 max-w-xl">
                                Estimativa considerando lançamentos do mês, faturas, parcelas e contas fixas previstas.
                                Não considera gastos futuros que ainda não foram cadastrados.
                            </p>
                        </div>

                        <div x-data="{ breakdownOpen: false }" class="lg:max-w-sm w-full shrink-0">
                            <button type="button" @click="breakdownOpen = !breakdownOpen"
                                class="text-xs text-slate-500 hover:text-slate-700 underline underline-offset-2">
                                <span x-text="breakdownOpen ? 'Ocultar cálculo' : 'Como calculamos?'"></span>
                            </button>
                            <div x-show="breakdownOpen" x-cloak
                                class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 space-y-1.5 tabular-nums">
                                <div class="flex justify-between gap-4">
                                    <span>Receitas do mês</span>
                                    <span id="projected-breakdown-income" class="font-medium text-emerald-700"></span>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <span>Despesas já lançadas</span>
                                    <span id="projected-breakdown-expenses" class="font-medium text-red-600"></span>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <span>A pagar no mês</span>
                                    <span id="projected-breakdown-payable" class="font-medium text-red-600"></span>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <span>Contas fixas previstas</span>
                                    <span id="projected-breakdown-recurring" class="font-medium text-red-600"></span>
                                </div>
                                <div class="border-t border-slate-200 pt-2 flex justify-between gap-4 font-semibold text-slate-800">
                                    <span>Saldo projetado</span>
                                    <span id="projected-breakdown-total"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Central de alertas --}}
                <div id="dashboard-alerts-section" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-700">Precisa da sua atenção</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Alertas gerados a partir dos seus orçamentos, faturas, parcelas e vencimentos.</p>
                    </div>
                    <div id="dashboard-alerts-content"></div>
                </div>

                {{-- Próximos compromissos --}}
                <div id="future-commitments-section" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-700">Próximos compromissos</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Veja quanto já está previsto para os próximos meses.</p>
                    </div>
                    <div id="future-commitments-content"></div>
                    <p id="future-commitments-note" class="text-[11px] text-slate-400 mt-4 hidden"></p>
                </div>

                {{-- Como interpretar os números (colapsável — não ocupa espaço até o usuário pedir) --}}
                <div x-data="{ metricsHelpOpen: false }" class="text-center px-2">
                    <button type="button" @click="metricsHelpOpen = !metricsHelpOpen"
                        class="text-xs text-slate-500 hover:text-slate-700 underline underline-offset-2">
                        <span x-text="metricsHelpOpen ? 'Ocultar explicação' : 'Entenda os números'"></span>
                    </button>
                    <div x-show="metricsHelpOpen" x-cloak
                        class="mt-3 text-left rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 space-y-1.5">
                        <p class="text-slate-700 font-medium">Os valores podem ser diferentes — e isso é esperado.</p>
                        <p><strong class="text-slate-700">Despesas do mês</strong> — pagamentos à vista e contas fixas. Não inclui fatura de cartão.</p>
                        <p><strong class="text-slate-700">A pagar no mês</strong> — faturas de cartão e parcelas com vencimento neste mês.</p>
                        <p><strong class="text-slate-700">Saldo projetado</strong> — estimativa do que sobra ou falta até o fim do mês, incluindo faturas e contas fixas previstas.</p>
                        <p><strong class="text-slate-700">Onde o dinheiro foi</strong> — gráfico por categoria (compras à vista + parcelas).</p>
                    </div>
                </div>

                {{-- A pagar no mês — Cartões / Parcelas --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-200">
                            <h3 class="text-sm font-semibold text-slate-700">A pagar no mês — Cartões</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Valor total de cada fatura com vencimento neste mês</p>
                        </div>
                        <div id="payables-cards-list" class="p-5 space-y-3 text-sm"></div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-5 py-4 border-b border-slate-200">
                            <h3 class="text-sm font-semibold text-slate-700">A pagar no mês — Parcelas de compras</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Empréstimos e financiamentos com parcela vencendo neste mês</p>
                        </div>
                        <div id="payables-loans-list" class="p-5 space-y-3 text-sm"></div>
                    </div>
                </div>

                {{-- Gastos por categoria --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-1">Onde o dinheiro foi</h3>
                    <p class="text-xs text-slate-400 mb-5">Despesas do mês por categoria (compras à vista e parcelas de empréstimo)</p>

                    @if(count($spendingByCategory) === 0)
                        <p class="text-sm text-slate-400 text-center py-6">
                            Nenhuma despesa registrada neste mês.
                        </p>
                    @else
                        @php
                            $categoryHexColors = ['#16a34a', '#3b82f6', '#f59e0b', '#a855f7', '#ef4444', '#94a3b8'];
                            $spendingTotal = collect($spendingByCategory)->sum('total');
                            $conicStops = [];
                            $cursor = 0;
                            foreach ($spendingByCategory as $i => $item) {
                                $share = $spendingTotal > 0 ? ($item['total'] / $spendingTotal) * 100 : 0;
                                $next = min($cursor + $share, 100);
                                $color = $categoryHexColors[$i % count($categoryHexColors)];
                                if ($share > 0) {
                                    $conicStops[] = "{$color} {$cursor}% {$next}%";
                                }
                                $cursor = $next;
                            }
                            $conicStyle = count($conicStops)
                                ? 'conic-gradient('.implode(', ', $conicStops).')'
                                : 'conic-gradient(#e2e8f0 0% 100%)';
                        @endphp
                        <div class="flex flex-col sm:flex-row items-center gap-8">
                            <div class="relative w-44 h-44 rounded-full shrink-0" style="background: {{ $conicStyle }}">
                                <div class="absolute inset-5 bg-white rounded-full flex flex-col items-center justify-center text-center shadow-sm">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400 font-medium">Total</span>
                                    <span class="text-sm font-bold text-slate-900 tabular-nums leading-tight mt-0.5">
                                        R$ {{ number_format($spendingTotal, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 w-full space-y-3">
                                @foreach($spendingByCategory as $index => $item)
                                    @php
                                        $share = $spendingTotal > 0 ? round(($item['total'] / $spendingTotal) * 100, 1) : 0;
                                        $hex = $categoryHexColors[$index % count($categoryHexColors)];
                                    @endphp
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $hex }}"></span>
                                            <span class="text-sm font-medium text-slate-700 truncate">{{ $item['category'] }}</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-sm font-semibold text-slate-900 tabular-nums">
                                                R$ {{ number_format($item['total'], 2, ',', '.') }}
                                            </span>
                                            <span class="text-xs text-slate-400 ml-1 tabular-nums">({{ $share }}%)</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

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
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 rounded-t-xl">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-700">Receita × Despesa — últimos 6 meses</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Comparativo mês a mês para acompanhar sua evolução</p>
                            </div>
                            <div class="flex gap-4">
                                <span class="flex items-center gap-1 text-xs text-slate-500">
                                    <span class="inline-block w-3 h-3 rounded-sm bg-emerald-500"></span> Receita
                                </span>
                                <span class="flex items-center gap-1 text-xs text-slate-500">
                                    <span class="inline-block w-3 h-3 rounded-sm bg-red-400"></span> Despesa
                                </span>
                            </div>
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

                {{-- Link para ver todas as movimentações do mês --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5
                            flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            Movimentações de {{ $monthLabel }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Abre a lista completa de receitas e despesas de {{ $monthLabel }}, já filtrada por este mês
                        </p>
                    </div>
                    <a href="{{ route('transactions.index') }}?month={{ $year }}-{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium
                              text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                        Ver movimentações
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
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
        const projectedBalanceEl = document.getElementById('dashboard-projected-balance');
        const projectedHintEl = document.getElementById('dashboard-projected-hint');
        const projectedBreakdownIncomeEl = document.getElementById('projected-breakdown-income');
        const projectedBreakdownExpensesEl = document.getElementById('projected-breakdown-expenses');
        const projectedBreakdownPayableEl = document.getElementById('projected-breakdown-payable');
        const projectedBreakdownRecurringEl = document.getElementById('projected-breakdown-recurring');
        const projectedBreakdownTotalEl = document.getElementById('projected-breakdown-total');
        const alertsContentEl = document.getElementById('dashboard-alerts-content');
        const futureCommitmentsContentEl = document.getElementById('future-commitments-content');
        const futureCommitmentsNoteEl = document.getElementById('future-commitments-note');
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

            renderProjectedBalance(cards.projected_balance ?? {});
        };

        const alertStyles = {
            danger: {
                wrapper: 'bg-red-50 border-red-200',
                dot: 'bg-red-500',
                title: 'text-red-900',
                message: 'text-red-800',
            },
            warning: {
                wrapper: 'bg-amber-50 border-amber-200',
                dot: 'bg-amber-500',
                title: 'text-amber-900',
                message: 'text-amber-800',
            },
            info: {
                wrapper: 'bg-blue-50 border-blue-200',
                dot: 'bg-blue-500',
                title: 'text-blue-900',
                message: 'text-blue-800',
            },
        };

        const renderAlerts = (alerts = {}) => {
            if (!alertsContentEl) return;

            const items = alerts.items ?? [];

            if (!items.length) {
                alertsContentEl.innerHTML = `
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-5 text-center">
                        <p class="text-sm font-semibold text-emerald-800">Tudo certo por enquanto</p>
                        <p class="text-xs text-emerald-700 mt-1">Não encontramos alertas importantes para este mês.</p>
                    </div>
                `;
                return;
            }

            const listHtml = items.map((alert) => {
                const styles = alertStyles[alert.severity] ?? alertStyles.info;
                const action = alert.url
                    ? `<a href="${alert.url}" class="inline-flex items-center text-xs font-medium text-slate-600 hover:text-slate-900 underline underline-offset-2 shrink-0">${alert.action_label ?? 'Ver detalhes'}</a>`
                    : '';

                return `
                    <div class="flex items-start gap-3 p-3 rounded-lg border ${styles.wrapper}">
                        <span class="mt-1.5 h-2 w-2 rounded-full ${styles.dot} shrink-0"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium ${styles.title}">${alert.title ?? 'Alerta'}</p>
                            <p class="text-xs mt-0.5 ${styles.message}">${alert.message ?? ''}</p>
                        </div>
                        ${action}
                    </div>
                `;
            }).join('');

            const moreHtml = alerts.has_more
                ? `<p class="text-xs text-slate-500 text-center pt-1">+ ${alerts.more_count} ${alerts.more_count === 1 ? 'ponto de atenção' : 'pontos de atenção'}</p>`
                : '';

            alertsContentEl.innerHTML = `<div class="space-y-2">${listHtml}${moreHtml}</div>`;
        };

        const renderFutureCommitments = (commitments = {}) => {
            if (!futureCommitmentsContentEl) return;

            const months = commitments.months ?? [];
            const note = commitments.note ?? '';

            if (futureCommitmentsNoteEl) {
                if (note) {
                    futureCommitmentsNoteEl.textContent = note;
                    futureCommitmentsNoteEl.classList.remove('hidden');
                } else {
                    futureCommitmentsNoteEl.textContent = '';
                    futureCommitmentsNoteEl.classList.add('hidden');
                }
            }

            if (!months.length || !commitments.has_commitments) {
                futureCommitmentsContentEl.innerHTML = `
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-center">
                        <p class="text-sm font-semibold text-slate-700">Nenhum compromisso futuro previsto</p>
                        <p class="text-xs text-slate-500 mt-1">Quando você cadastrar compras parceladas ou contas fixas, elas aparecerão aqui.</p>
                        <div class="mt-4 flex flex-col sm:flex-row items-center justify-center gap-2">
                            <a href="{{ route('transactions.create') }}"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                                Adicionar transação
                            </a>
                            <a href="{{ route('recurring-templates.create') }}"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                                Cadastrar conta fixa
                            </a>
                        </div>
                    </div>
                `;
                return;
            }

            const cardsHtml = months.map((month) => {
                const estimatedBadge = month.is_estimated
                    ? '<span class="inline-flex items-center px-2 py-0.5 text-[10px] rounded-full bg-amber-100 text-amber-700">Inclui valores previstos</span>'
                    : '';

                return `
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">${month.label ?? ''}</p>
                                <p class="text-xs text-slate-500 mt-0.5">${formatMoney(month.total ?? 0)} comprometidos</p>
                            </div>
                            ${estimatedBadge}
                        </div>
                        <div class="space-y-1 text-xs text-slate-600 tabular-nums">
                            <div class="flex justify-between gap-4">
                                <span>Parcelas</span>
                                <span class="font-medium text-slate-800">${formatMoney(month.installments_total ?? 0)}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span>Contas fixas previstas</span>
                                <span class="font-medium text-slate-800">${formatMoney(month.recurring_total ?? 0)}</span>
                            </div>
                            <div class="border-t border-slate-200 pt-1 flex justify-between gap-4 font-semibold text-slate-800">
                                <span>Total comprometido</span>
                                <span>${formatMoney(month.total ?? 0)}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            futureCommitmentsContentEl.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    ${cardsHtml}
                </div>
                <p class="text-[11px] text-slate-400 mt-3">Valores já previstos com base nas suas parcelas e contas fixas cadastradas.</p>
            `;
        };

        const formatBreakdownDeduction = (value) => {
            const amount = Number(value ?? 0);
            if (!Number.isFinite(amount) || amount === 0) {
                return formatMoney(0);
            }
            return `− ${formatMoney(amount)}`;
        };

        const renderProjectedBalance = (projected = {}) => {
            const amount = Number(projected.amount ?? 0);
            const isNegative = projected.is_negative ?? amount < 0;

            projectedBalanceEl.textContent = formatMoney(amount);
            projectedBalanceEl.classList.remove('text-emerald-700', 'text-red-600', 'text-slate-900');
            projectedBalanceEl.classList.add(isNegative ? 'text-red-600' : 'text-emerald-700');

            projectedHintEl.textContent = isNegative
                ? 'Atenção: sua projeção indica falta de saldo até o fim do mês.'
                : 'Estimativa do que pode sobrar até o fim do mês.';

            projectedHintEl.classList.remove('text-red-600', 'text-slate-500');
            projectedHintEl.classList.add(isNegative ? 'text-red-600' : 'text-slate-500');

            projectedBreakdownIncomeEl.textContent = formatMoney(projected.income ?? 0);
            projectedBreakdownExpensesEl.textContent = formatBreakdownDeduction(projected.expenses_recorded ?? 0);
            projectedBreakdownPayableEl.textContent = formatBreakdownDeduction(projected.payable ?? 0);
            projectedBreakdownRecurringEl.textContent = formatBreakdownDeduction(projected.recurring_projection ?? 0);
            projectedBreakdownTotalEl.textContent = formatMoney(amount);
            projectedBreakdownTotalEl.classList.remove('text-emerald-700', 'text-red-600');
            projectedBreakdownTotalEl.classList.add(isNegative ? 'text-red-600' : 'text-emerald-700');
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
            if (!cashflowBodyEl) return;
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
            if (projectedBalanceEl) projectedBalanceEl.textContent = '-';
            if (projectedHintEl) projectedHintEl.textContent = 'Não foi possível calcular a projeção.';
            if (alertsContentEl) alertsContentEl.innerHTML = '<p class="text-xs text-slate-400">Não foi possível carregar os alertas.</p>';
            if (futureCommitmentsContentEl) futureCommitmentsContentEl.innerHTML = '<p class="text-xs text-slate-400">Não foi possível carregar os próximos compromissos.</p>';

            payablesCardsListEl.innerHTML = '<div class="text-xs text-slate-400">Erro ao carregar cartões.</div>';
            payablesLoansListEl.innerHTML = '<div class="text-xs text-slate-400">Erro ao carregar empréstimos.</div>';
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
                renderAlerts(data?.alerts ?? {});
                renderFutureCommitments(data?.future_commitments ?? {});
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
