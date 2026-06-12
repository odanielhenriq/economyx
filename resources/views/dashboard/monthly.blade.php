<x-app-layout>
    <div id="dashboard-wrapper" x-data="{ loading: true }" class="-mt-1">

        {{-- Toolbar compacta (substitui o header sticky) --}}
        <div
            x-data="{
                exportOpen: false,
                metricsHelpOpen: false,
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
            class="flex items-center justify-between gap-2 mb-3 min-h-[2.5rem]"
        >
            <div class="flex items-center gap-2 min-w-0">
                <button type="button"
                    @click="$dispatch('open-sidebar')"
                    class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-white hover:text-slate-900 border border-transparent hover:border-slate-200 transition shrink-0"
                    aria-label="Abrir menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-1 py-0.5">
                    <button type="button" @click="prev()" aria-label="Mês anterior"
                        class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition text-sm">
                        ←
                    </button>
                    <span x-text="label" class="text-sm font-semibold text-slate-800 capitalize min-w-[7.5rem] sm:min-w-[8.5rem] text-center px-1 truncate" aria-live="polite"></span>
                    <button type="button" @click="next()" aria-label="Próximo mês"
                        class="p-1.5 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition text-sm">
                        →
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <div class="relative" @click.outside="metricsHelpOpen = false">
                    <button type="button" @click="metricsHelpOpen = !metricsHelpOpen"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500/40 focus-visible:ring-offset-1"
                        title="Entenda os números"
                        aria-label="Entenda os números">
                        <span class="text-xs font-semibold">?</span>
                    </button>
                    <div x-show="metricsHelpOpen" x-cloak x-transition
                        class="absolute right-0 top-full z-30 mt-1.5 w-72 rounded-lg border border-slate-200 bg-white shadow-lg px-3 py-2.5 text-[11px] text-slate-600 space-y-1">
                        <p class="text-slate-700 font-medium">Os valores podem ser diferentes — e isso é esperado.</p>
                        <p><strong class="text-slate-700">Despesas</strong> — à vista e fixas. Não inclui fatura de cartão.</p>
                        <p><strong class="text-slate-700">A pagar</strong> — faturas e parcelas com vencimento neste mês.</p>
                        <p><strong class="text-slate-700">Saldo projetado</strong> — estimativa até o fim do mês, incluindo faturas e fixas previstas.</p>
                        <p><strong class="text-slate-700">Meta de economia</strong> — quanto quer guardar vs. saldo projetado.</p>
                        <p><strong class="text-slate-700">Categorias</strong> — despesas por categoria (à vista + parcelas).</p>
                    </div>
                </div>
                <div class="relative" @click.outside="exportOpen = false">
                    <button type="button" @click="exportOpen = !exportOpen"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-slate-200 text-slate-700 text-xs font-medium rounded-lg hover:bg-slate-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500/40 focus-visible:ring-offset-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="hidden sm:inline">Exportar</span>
                        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="exportOpen" x-transition style="display:none"
                        class="absolute right-0 mt-1 w-60 rounded-lg border border-slate-200 bg-white py-1 shadow-lg z-20">
                        <a href="{{ route('transactions.export') }}"
                           class="block px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                            Exportar CSV
                        </a>
                        <a :href="`{{ route('reports.monthly.pdf') }}?year=${year}&month=${month}`"
                           class="block px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                            Relatório mensal PDF
                        </a>
                        <div class="border-t border-slate-100 mx-2 my-1"></div>
                        <p class="px-4 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Mais opções</p>
                        <a href="{{ route('export.json') }}" download="economyx-{{ now()->format('Y-m') }}.json"
                           class="block px-4 py-2.5 text-left hover:bg-slate-50">
                            <span class="block text-xs font-medium text-slate-500">Exportar JSON</span>
                            <span class="block text-[11px] text-slate-400 mt-0.5">Avançado — backup ou integração externa</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">

            <x-first-use-checklist
                :has-transactions="$hasTransactions"
                :has-credit-cards="$hasCreditCards"
                :has-budgets="$hasBudgets"
            />

            {{-- Skeleton: visível enquanto carrega --}}
            <div x-show="loading" class="grid grid-cols-12 gap-3">
                <div class="col-span-6 lg:col-span-3 h-[4.5rem] bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-6 lg:col-span-3 h-[4.5rem] bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-6 lg:col-span-3 h-[4.5rem] bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-6 lg:col-span-3 h-[4.5rem] bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 lg:col-span-8 grid grid-cols-8 gap-3">
                    <div class="col-span-8 lg:col-span-3 h-28 bg-slate-200 rounded-xl animate-pulse"></div>
                    <div class="col-span-8 lg:col-span-5 h-28 bg-slate-200 rounded-xl animate-pulse"></div>
                </div>
                <div class="col-span-12 lg:col-span-4 h-28 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 sm:col-span-4 h-24 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 sm:col-span-4 h-24 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 sm:col-span-4 h-24 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 lg:col-span-5 h-48 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 lg:col-span-7 h-48 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 lg:col-span-6 h-32 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="col-span-12 lg:col-span-6 h-32 bg-slate-200 rounded-xl animate-pulse"></div>
            </div>

            {{-- Conteúdo real: visível após carregar --}}
            <div x-show="!loading" style="display:none" class="grid grid-cols-12 gap-3 items-start">

                @php
                    $curIncome  = $chartData[5]['income']  ?? 0;
                    $prevIncome = $previousMonthData['income']  ?? 0;
                    $curExpense = $chartData[5]['expense'] ?? 0;
                    $prevExpense = $previousMonthData['expense'] ?? 0;

                    $incomeVar  = $prevIncome  > 0 ? (($curIncome  - $prevIncome)  / $prevIncome)  * 100 : null;
                    $expenseVar = $prevExpense > 0 ? (($curExpense - $prevExpense) / $prevExpense) * 100 : null;
                @endphp

                {{-- Linha 1: KPIs (3 cols cada) --}}
                <div class="col-span-6 lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-slate-500">Receitas</span>
                        <div class="w-6 h-6 bg-emerald-100 rounded-md flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                            </svg>
                        </div>
                    </div>
                    <p id="dashboard-income" class="text-xl font-bold text-emerald-700 tabular-nums leading-none"></p>
                    <p class="text-[10px] mt-1 font-medium min-h-[14px] {{ $incomeVar !== null ? ($incomeVar >= 0 ? 'text-green-600' : 'text-red-500') : 'invisible' }}">
                        @if($incomeVar !== null)
                            {{ $incomeVar >= 0 ? '↑' : '↓' }} {{ number_format(abs($incomeVar), 1) }}%
                        @else
                            &nbsp;
                        @endif
                    </p>
                </div>

                <div class="col-span-6 lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-slate-500">Despesas</span>
                        <div class="w-6 h-6 bg-red-100 rounded-md flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.306-4.307a11.95 11.95 0 015.814 5.519l2.74 1.22m0 0l-5.94 2.28m5.94-2.28l-2.28-5.941" />
                            </svg>
                        </div>
                    </div>
                    <p id="dashboard-expense" class="text-xl font-bold text-red-600 tabular-nums leading-none"></p>
                    <p class="text-[10px] mt-1 font-medium min-h-[14px] {{ $expenseVar !== null ? ($expenseVar > 0 ? 'text-red-500' : 'text-green-600') : 'invisible' }}">
                        @if($expenseVar !== null)
                            {{ $expenseVar > 0 ? '↑' : '↓' }} {{ number_format(abs($expenseVar), 1) }}%
                        @else
                            &nbsp;
                        @endif
                    </p>
                </div>

                <div class="col-span-6 lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-slate-500">Saldo</span>
                        <div class="w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 5.277a1.125 1.125 0 01-.12 1.06 48.516 48.516 0 01-7.5 0 1.125 1.125 0 01-.12-1.06l2.62-5.277m0 0A48.416 48.416 0 0112 4.5" />
                            </svg>
                        </div>
                    </div>
                    <p id="dashboard-balance" class="text-xl font-bold tabular-nums leading-none"></p>
                    <p class="text-[10px] mt-1 min-h-[14px] invisible" aria-hidden="true">&nbsp;</p>
                </div>

                <div class="col-span-6 lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-slate-500">A pagar</span>
                        <div class="w-6 h-6 bg-slate-100 rounded-md flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                    </div>
                    <p id="dashboard-payable-total" class="text-xl font-bold text-slate-900 tabular-nums leading-none"></p>
                    <p id="dashboard-payable-breakdown" class="text-[10px] text-slate-500 tabular-nums truncate min-h-[14px]"></p>
                    <button type="button"
                        onclick="document.getElementById('payables-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                        class="text-[10px] text-slate-500 hover:text-slate-700 underline underline-offset-2 mt-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500/40 rounded">
                        Ver detalhes ↓
                    </button>
                </div>

                {{-- Linha 2: Saldo projetado + Meta (par) + Alertas --}}
                <div class="col-span-12 grid grid-cols-12 gap-3 items-start">
                    <div class="col-span-12 lg:col-span-8 grid grid-cols-8 gap-3 items-stretch">
                        <div id="projected-balance-card" class="col-span-8 lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5 relative flex flex-col h-full">
                            <div class="flex items-center justify-between gap-2 shrink-0">
                                <span class="text-xs font-medium text-slate-500">Saldo projetado</span>
                                <div x-data="{ breakdownOpen: false }" class="shrink-0 relative">
                                    <button type="button" @click="breakdownOpen = !breakdownOpen"
                                        class="text-[10px] text-slate-500 hover:text-slate-700 underline underline-offset-2 whitespace-nowrap rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500/40 focus-visible:ring-offset-1">
                                        <span x-text="breakdownOpen ? 'Ocultar' : 'Como calculamos?'"></span>
                                    </button>
                                    <div x-show="breakdownOpen" x-cloak
                                        class="absolute z-10 top-full right-0 mt-1 w-52 rounded-lg border border-slate-200 bg-white shadow-lg px-3 py-2 text-[10px] text-slate-600 space-y-1 tabular-nums">
                                        <div class="flex justify-between gap-2"><span>Receitas</span><span id="projected-breakdown-income" class="font-medium text-emerald-700"></span></div>
                                        <div class="flex justify-between gap-2"><span>Despesas</span><span id="projected-breakdown-expenses" class="font-medium text-red-600"></span></div>
                                        <div class="flex justify-between gap-2"><span>A pagar</span><span id="projected-breakdown-payable" class="font-medium text-red-600"></span></div>
                                        <div class="flex justify-between gap-2"><span>Fixas prev.</span><span id="projected-breakdown-recurring" class="font-medium text-red-600"></span></div>
                                        <div class="border-t border-slate-200 pt-1 flex justify-between gap-2 font-semibold"><span>Total</span><span id="projected-breakdown-total"></span></div>
                                    </div>
                                </div>
                            </div>
                            <p id="dashboard-projected-balance" class="text-xl font-bold tabular-nums text-slate-900 mt-0.5 leading-none shrink-0"></p>
                            <p id="dashboard-projected-hint" class="text-[11px] text-slate-500 mt-1 line-clamp-1 shrink-0"></p>
                            <div id="projected-breakdown-inline" class="border-t border-slate-100 pt-1.5 mt-1.5 space-y-0.5 text-[10px] text-slate-600 tabular-nums flex-1">
                                <div class="flex justify-between gap-2"><span>Receitas</span><span id="projected-inline-income" class="font-medium text-emerald-700"></span></div>
                                <div class="flex justify-between gap-2"><span>Despesas</span><span id="projected-inline-expenses" class="font-medium text-red-600"></span></div>
                                <div class="flex justify-between gap-2"><span>A pagar</span><span id="projected-inline-payable" class="font-medium text-red-600"></span></div>
                                <div class="flex justify-between gap-2"><span>Fixas prev.</span><span id="projected-inline-recurring" class="font-medium text-red-600"></span></div>
                            </div>
                        </div>

                        <div id="savings-goal-card" class="col-span-8 lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5 flex flex-col h-full">
                            <div id="savings-goal-content" class="flex flex-col h-full"></div>
                        </div>
                    </div>

                    <div id="dashboard-alerts-section" class="col-span-12 lg:col-span-4 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5">
                        <div class="mb-1.5">
                            <h3 class="text-xs font-semibold text-slate-700">Precisa da sua atenção</h3>
                            <p class="text-[10px] text-slate-400">Alertas do mês</p>
                        </div>
                        <div id="dashboard-alerts-content"></div>
                    </div>
                </div>

                {{-- Acompanhamento --}}
                <div class="col-span-12 pt-1">
                    <p id="acompanhamento" class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Acompanhamento</p>
                </div>
                <div class="col-span-12 sm:col-span-4 bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex flex-col">
                    <h3 class="text-xs font-semibold text-slate-700">Próximos compromissos</h3>
                    <div id="follow-up-commitments-content" class="text-slate-600 mt-1.5 flex-1">
                        <span class="text-slate-400 text-xs">Carregando…</span>
                    </div>
                </div>
                <a href="{{ route('installment-purchases.index') }}"
                   class="col-span-12 sm:col-span-4 bg-white rounded-xl border border-slate-200 shadow-sm p-3 hover:border-slate-300 transition group flex flex-col">
                    <h3 class="text-xs font-semibold text-slate-700 group-hover:text-slate-900">Compras parceladas</h3>
                    <div class="mt-1.5 flex-1">
                        @if(($followUpInstallments['active_count'] ?? 0) > 0)
                            <p class="text-base font-semibold text-slate-800 tabular-nums leading-tight">
                                {{ $followUpInstallments['active_count'] }} {{ $followUpInstallments['active_count'] === 1 ? 'ativa' : 'ativas' }}
                                · R$ {{ number_format($followUpInstallments['remaining_total'] ?? 0, 2, ',', '.') }}
                            </p>
                        @else
                            <p class="text-sm text-slate-500">Nenhuma ativa</p>
                        @endif
                    </div>
                    <span class="text-[10px] font-medium text-green-700 mt-2 shrink-0">Ver compras →</span>
                </a>
                <a href="{{ route('shared-expenses.index') }}?year={{ $year }}&month={{ $month }}"
                   class="col-span-12 sm:col-span-4 bg-white rounded-xl border border-slate-200 shadow-sm p-3 hover:border-slate-300 transition group flex flex-col">
                    <h3 class="text-xs font-semibold text-slate-700 group-hover:text-slate-900">Gastos compartilhados</h3>
                    <div class="mt-1.5 flex-1">
                        @if($followUpShared['has_shared_expenses'] ?? false)
                            <p class="text-base font-semibold text-slate-800 tabular-nums leading-tight">
                                R$ {{ number_format($followUpShared['pending_settlement'] ?? 0, 2, ',', '.') }} pendente
                            </p>
                        @else
                            <p class="text-sm text-slate-500">Nenhum no mês</p>
                        @endif
                    </div>
                    <span class="text-[10px] font-medium text-green-700 mt-2 shrink-0">Ver gastos →</span>
                </a>

                {{-- Análises: categorias + evolução lado a lado --}}
                <div class="col-span-12 lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-sm p-3">
                    <h2 class="text-xs font-semibold text-slate-800 mb-0.5">Onde o dinheiro foi</h2>
                    <p class="text-[10px] text-slate-400 mb-3">Despesas por categoria</p>

                    @if(count($spendingByCategory) === 0)
                        <p class="text-xs text-slate-400 text-center py-4">Nenhuma despesa neste mês.</p>
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
                        <div class="flex items-start gap-3">
                            <div class="relative w-24 h-24 rounded-full shrink-0" style="background: {{ $conicStyle }}">
                                <div class="absolute inset-3 bg-white rounded-full flex flex-col items-center justify-center text-center">
                                    <span class="text-[9px] uppercase text-slate-400">Total</span>
                                    <span class="text-xs font-bold text-slate-900 tabular-nums">R$ {{ number_format($spendingTotal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0 grid grid-cols-1 gap-1.5">
                                @foreach($spendingByCategory as $index => $item)
                                    @php
                                        $share = $spendingTotal > 0 ? round(($item['total'] / $spendingTotal) * 100, 1) : 0;
                                        $hex = $categoryHexColors[$index % count($categoryHexColors)];
                                    @endphp
                                    <div class="flex items-center justify-between gap-2 text-[11px]">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $hex }}"></span>
                                            <span class="font-medium text-slate-700 truncate">{{ $item['category'] }}</span>
                                        </div>
                                        <span class="tabular-nums text-slate-600 shrink-0">{{ $share }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <script>window._chartData = @json($chartData);</script>
                <div
                    x-data="{
                        months: window._chartData,
                        get maxVal() {
                            return Math.max(...this.months.flatMap(m => [m.income, m.expense]), 1);
                        },
                        get showNoIncomeHint() {
                            return this.months.some(m => m.expense > 0) && this.months.every(m => m.income === 0);
                        },
                        barHeight(val) {
                            return Math.round((val / this.maxVal) * 88) + 'px';
                        }
                    }"
                    class="col-span-12 lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-sm p-3"
                >
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div>
                            <h2 class="text-xs font-semibold text-slate-800">Receita × Despesa</h2>
                            <p class="text-[10px] text-slate-400">Últimos 6 meses</p>
                            <p x-show="showNoIncomeHint" x-cloak class="text-[10px] text-slate-400 mt-0.5 italic">
                                Sem receitas registradas neste período.
                            </p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <span class="flex items-center gap-1 text-[10px] text-slate-500"><span class="w-2 h-2 rounded-sm bg-emerald-500"></span>Rec.</span>
                            <span class="flex items-center gap-1 text-[10px] text-slate-500"><span class="w-2 h-2 rounded-sm bg-red-400"></span>Desp.</span>
                        </div>
                    </div>
                    <div x-show="months.every(m => m.income === 0 && m.expense === 0)"
                         class="py-4 text-[11px] text-center text-slate-400">Sem movimentações.</div>
                    <div x-show="months.some(m => m.income > 0 || m.expense > 0)"
                         class="flex items-end justify-around gap-1" style="height: 108px">
                        <template x-for="m in months" :key="m.label">
                            <div class="flex flex-col items-center flex-1 min-w-0">
                                <div class="flex items-end justify-center gap-0.5" style="height: 88px">
                                    <div class="w-3 rounded-t bg-emerald-500" :style="'height: ' + barHeight(m.income)"></div>
                                    <div class="w-3 rounded-t bg-red-400" :style="'height: ' + barHeight(m.expense)"></div>
                                </div>
                                <span class="mt-0.5 text-[9px] text-slate-400 truncate w-full text-center" x-text="m.label"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- A pagar detalhado: dois cards bento --}}
                <div id="payables-section" class="col-span-12 lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5 scroll-mt-4">
                    <h2 class="text-xs font-semibold text-slate-800">Faturas do cartão</h2>
                    <p class="text-[10px] text-slate-400 mb-2">Vencimento em {{ $monthLabel }}</p>
                    <div id="payables-cards-list" class="divide-y divide-slate-100"></div>
                </div>
                <div class="col-span-12 lg:col-span-6 bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5">
                    <h2 class="text-xs font-semibold text-slate-800">Parcelas / empréstimos</h2>
                    <p class="text-[10px] text-slate-400 mb-2">Vencimento em {{ $monthLabel }}</p>
                    <div id="payables-loans-list" class="divide-y divide-slate-100"></div>
                </div>

                {{-- Rodapé: movimentações --}}
                <div class="col-span-12 pt-1">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800">Movimentações de {{ $monthLabel }}</p>
                            <p class="text-[11px] text-slate-400 truncate">Lista completa filtrada por este mês</p>
                        </div>
                        <a href="{{ route('transactions.index') }}?month={{ $year }}-{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition shrink-0">
                            Ver movimentações
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal meta de economia --}}
    <div id="savings-goal-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" aria-hidden="true">
        <div id="savings-goal-modal-backdrop" class="absolute inset-0 bg-slate-900/40"></div>
        <div class="relative w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-xl p-5">
            <h3 class="text-sm font-semibold text-slate-800">Meta de economia</h3>
            <p class="text-xs text-slate-500 mt-1">Sua meta será comparada com o saldo projetado do mês.</p>
            <form id="savings-goal-form" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="year" id="savings-goal-year" value="{{ $year }}">
                <input type="hidden" name="month" id="savings-goal-month" value="{{ $month }}">
                <div>
                    <label for="savings-goal-amount" class="block text-xs font-medium text-slate-500 mb-1">
                        Quanto você quer guardar neste mês?
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">R$</span>
                        <input type="number" id="savings-goal-amount" name="target_amount" min="0.01" step="0.01" required
                            class="w-full pl-10 pr-3 py-2 text-sm border border-slate-200 rounded-lg"
                            placeholder="500,00">
                    </div>
                </div>
                <div>
                    <label for="savings-goal-note" class="block text-xs font-medium text-slate-500 mb-1">Observação (opcional)</label>
                    <textarea id="savings-goal-note" name="note" rows="2" maxlength="500"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg resize-none"
                        placeholder="Ex.: reserva para viagem"></textarea>
                </div>
                <p id="savings-goal-form-error" class="text-xs text-red-600 hidden"></p>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" id="savings-goal-cancel"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit" id="savings-goal-submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-60">
                        Salvar meta
                    </button>
                </div>
            </form>
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
        const projectedInlineIncomeEl = document.getElementById('projected-inline-income');
        const projectedInlineExpensesEl = document.getElementById('projected-inline-expenses');
        const projectedInlinePayableEl = document.getElementById('projected-inline-payable');
        const projectedInlineRecurringEl = document.getElementById('projected-inline-recurring');
        const alertsContentEl = document.getElementById('dashboard-alerts-content');
        const followUpCommitmentsContentEl = document.getElementById('follow-up-commitments-content');
        const payablesCardsListEl = document.getElementById('payables-cards-list');
        const payablesLoansListEl = document.getElementById('payables-loans-list');
        const cashflowBodyEl = document.getElementById('cashflow-body');
        const savingsGoalContentEl = document.getElementById('savings-goal-content');
        const savingsGoalModalEl = document.getElementById('savings-goal-modal');
        const savingsGoalFormEl = document.getElementById('savings-goal-form');
        const savingsGoalAmountEl = document.getElementById('savings-goal-amount');
        const savingsGoalNoteEl = document.getElementById('savings-goal-note');
        const savingsGoalErrorEl = document.getElementById('savings-goal-form-error');
        const savingsGoalSubmitEl = document.getElementById('savings-goal-submit');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        let currentSavingsGoal = null;

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

            const allItems = alerts.items ?? [];
            const items = allItems.slice(0, 3);
            const total = alerts.total ?? allItems.length;
            const hiddenCount = Math.max(0, total - items.length);

            if (!items.length) {
                alertsContentEl.innerHTML = `
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-2 text-center">
                        <p class="text-[11px] font-semibold text-emerald-800">Tudo certo</p>
                    </div>
                `;
                return;
            }

            const listHtml = items.map((alert) => {
                const styles = alertStyles[alert.severity] ?? alertStyles.info;
                const action = alert.url
                    ? `<a href="${alert.url}" class="text-[11px] font-medium text-slate-600 hover:text-slate-900 underline shrink-0">${alert.action_label ?? 'Ver'}</a>`
                    : '';

                return `
                    <div class="rounded-md border px-2 py-1.5 ${styles.wrapper}">
                        <div class="flex items-start gap-1.5">
                            <span class="mt-1 h-1.5 w-1.5 rounded-full ${styles.dot} shrink-0"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium ${styles.title} truncate">${alert.title ?? 'Alerta'}</p>
                                <p class="text-[11px] ${styles.message} line-clamp-2 leading-snug">${alert.message ?? ''}</p>
                            </div>
                            ${action}
                        </div>
                    </div>
                `;
            }).join('');

            const moreHtml = hiddenCount > 0
                ? `<p class="text-[10px] text-slate-500 text-center pt-1">+ ${hiddenCount} ${hiddenCount === 1 ? 'ponto' : 'pontos'}</p>`
                : '';

            alertsContentEl.innerHTML = `<div class="space-y-1.5">${listHtml}${moreHtml}</div>`;
        };

        const renderFollowUpCommitments = (commitments = {}) => {
            if (!followUpCommitmentsContentEl) return;

            const months = commitments.months ?? [];

            if (!months.length || !commitments.has_commitments) {
                followUpCommitmentsContentEl.innerHTML = `
                    <p class="text-sm text-slate-500">Nenhum compromisso previsto</p>
                    <p class="text-[11px] text-slate-400 mt-1">Parcelas e contas fixas aparecerão aqui.</p>
                `;
                return;
            }

            const activeMonths = months.filter((month) => Number(month.total ?? 0) > 0);
            const monthCount = activeMonths.length || months.length;
            const totalCommitted = months.reduce((sum, month) => sum + Number(month.total ?? 0), 0);
            const monthLabel = monthCount === 1 ? 'mês previsto' : 'meses previstos';

            followUpCommitmentsContentEl.innerHTML = `
                <p class="text-base font-semibold text-slate-800 tabular-nums leading-tight">
                    ${monthCount} ${monthLabel} · ${formatMoney(totalCommitted)}
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Parcelas e contas fixas cadastradas.</p>
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
                ? 'Projeção indica falta de saldo.'
                : 'Estimativa até o fim do mês.';

            projectedHintEl.classList.remove('text-red-600', 'text-slate-500');
            projectedHintEl.classList.add(isNegative ? 'text-red-600' : 'text-slate-500');

            projectedBreakdownIncomeEl.textContent = formatMoney(projected.income ?? 0);
            projectedBreakdownExpensesEl.textContent = formatBreakdownDeduction(projected.expenses_recorded ?? 0);
            projectedBreakdownPayableEl.textContent = formatBreakdownDeduction(projected.payable ?? 0);
            projectedBreakdownRecurringEl.textContent = formatBreakdownDeduction(projected.recurring_projection ?? 0);
            projectedBreakdownTotalEl.textContent = formatMoney(amount);
            projectedBreakdownTotalEl.classList.remove('text-emerald-700', 'text-red-600');
            projectedBreakdownTotalEl.classList.add(isNegative ? 'text-red-600' : 'text-emerald-700');

            if (projectedInlineIncomeEl) projectedInlineIncomeEl.textContent = formatMoney(projected.income ?? 0);
            if (projectedInlineExpensesEl) projectedInlineExpensesEl.textContent = formatBreakdownDeduction(projected.expenses_recorded ?? 0);
            if (projectedInlinePayableEl) projectedInlinePayableEl.textContent = formatBreakdownDeduction(projected.payable ?? 0);
            if (projectedInlineRecurringEl) projectedInlineRecurringEl.textContent = formatBreakdownDeduction(projected.recurring_projection ?? 0);
        };

        const openSavingsGoalModal = () => {
            if (!savingsGoalModalEl) return;

            savingsGoalAmountEl.value = currentSavingsGoal?.exists
                ? Number(currentSavingsGoal.target_amount ?? 0).toFixed(2)
                : '';
            savingsGoalNoteEl.value = currentSavingsGoal?.note ?? '';
            savingsGoalErrorEl.classList.add('hidden');
            savingsGoalErrorEl.textContent = '';
            savingsGoalModalEl.classList.remove('hidden');
            savingsGoalModalEl.classList.add('flex');
            savingsGoalModalEl.setAttribute('aria-hidden', 'false');
        };

        const closeSavingsGoalModal = () => {
            if (!savingsGoalModalEl) return;

            savingsGoalModalEl.classList.add('hidden');
            savingsGoalModalEl.classList.remove('flex');
            savingsGoalModalEl.setAttribute('aria-hidden', 'true');
        };

        const renderSavingsGoal = (goal = {}) => {
            if (!savingsGoalContentEl) return;

            currentSavingsGoal = goal;

            if (!goal.exists) {
                savingsGoalContentEl.innerHTML = `
                    <div class="flex flex-col h-full">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-xs font-semibold text-slate-700">Meta de economia</h3>
                                <p class="text-[10px] text-slate-500 mt-0.5 line-clamp-1">Compare com o saldo projetado.</p>
                            </div>
                            <button type="button" id="savings-goal-open" class="shrink-0 px-2.5 py-1 text-[11px] font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition">
                                Definir
                            </button>
                        </div>
                        <div class="flex-1" aria-hidden="true"></div>
                    </div>
                `;
                document.getElementById('savings-goal-open')?.addEventListener('click', openSavingsGoalModal);
                return;
            }

            const onTrack = goal.status === 'on_track';
            const statusClasses = onTrack
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-amber-50 text-amber-700 border-amber-200';
            const barColor = onTrack ? 'bg-emerald-500' : 'bg-amber-500';
            const progress = Math.min(100, Math.max(0, Number(goal.progress_percent ?? 0)));

            savingsGoalContentEl.innerHTML = `
                <div class="flex flex-col h-full space-y-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <h3 class="text-xs font-semibold text-slate-700">Meta de economia</h3>
                                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded-full border ${statusClasses}">
                                    ${goal.status_label ?? ''}
                                </span>
                            </div>
                            <p class="text-xl font-bold text-slate-900 tabular-nums leading-none mt-0.5">${formatMoney(goal.target_amount ?? 0)}</p>
                        </div>
                        <button type="button" id="savings-goal-edit" class="shrink-0 px-2 py-0.5 text-[10px] font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-md transition">
                            Editar
                        </button>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 mb-0.5">
                            <span>Projetado: ${formatMoney(goal.projected_balance ?? 0)}</span>
                            <span>${progress.toFixed(0)}%</span>
                        </div>
                        <div class="h-1 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full ${barColor} transition-all" style="width: ${progress}%"></div>
                        </div>
                    </div>
                    <p class="text-xs leading-snug ${onTrack ? 'text-emerald-700' : 'text-amber-700'} line-clamp-2">${goal.message ?? ''}</p>
                    ${goal.note ? `<p class="text-[10px] text-slate-400 truncate">Obs.: ${goal.note}</p>` : ''}
                </div>
            `;

            document.getElementById('savings-goal-edit')?.addEventListener('click', openSavingsGoalModal);
        };

        const renderPayablesCards = (list = []) => {
            payablesCardsListEl.innerHTML = '';

            if (!list.length) {
                payablesCardsListEl.innerHTML = '<div class="text-xs text-slate-400">Nenhuma fatura encontrada para este mês.</div>';
                return;
            }

            list.forEach((card) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center justify-between gap-2 py-1.5 text-sm';

                const info = document.createElement('div');
                info.className = 'min-w-0 flex-1';

                const title = document.createElement('div');
                title.className = 'text-xs font-medium text-slate-800 truncate';
                title.textContent = card?.card_name ?? 'Cartão';

                const due = document.createElement('div');
                due.className = 'text-[10px] text-slate-400';
                due.textContent = card?.due_date ?? '-';

                info.appendChild(title);
                info.appendChild(due);

                const amount = document.createElement('div');
                amount.className = 'text-xs font-semibold text-red-600 tabular-nums shrink-0';
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
                wrapper.className = 'flex items-center justify-between gap-2 py-1.5 text-sm';

                const info = document.createElement('div');
                info.className = 'min-w-0 flex-1';

                const title = document.createElement('div');
                title.className = 'text-xs font-medium text-slate-800 truncate';
                title.textContent = loan?.description ?? 'Parcela';

                const due = document.createElement('div');
                due.className = 'text-[10px] text-slate-400';
                due.textContent = loan?.due_date ?? '-';

                info.appendChild(title);
                info.appendChild(due);

                const amount = document.createElement('div');
                amount.className = 'text-xs font-semibold text-red-600 tabular-nums shrink-0';
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

                const dueCell = document.createElement('td');
                dueCell.className = 'px-4 py-3 text-slate-500 whitespace-nowrap';
                if (item?.due_date) {
                    const date = new Date(item.due_date);
                    dueCell.textContent = date.toLocaleDateString('pt-BR');
                } else {
                    dueCell.textContent = '-';
                }

                const descCell = document.createElement('td');
                descCell.className = 'px-4 py-3 text-slate-800';
                descCell.textContent = item?.description ?? '-';

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
            if (followUpCommitmentsContentEl) followUpCommitmentsContentEl.innerHTML = '<p class="text-xs text-slate-400">Não foi possível carregar os compromissos.</p>';
            if (savingsGoalContentEl) savingsGoalContentEl.innerHTML = '<p class="text-xs text-slate-400">Não foi possível carregar a meta de economia.</p>';

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
                renderSavingsGoal(data?.savings_goal ?? {});
                renderAlerts(data?.alerts ?? {});
                renderFollowUpCommitments(data?.future_commitments ?? {});
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

        document.getElementById('savings-goal-cancel')?.addEventListener('click', closeSavingsGoalModal);
        document.getElementById('savings-goal-modal-backdrop')?.addEventListener('click', closeSavingsGoalModal);

        savingsGoalFormEl?.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!savingsGoalFormEl || !savingsGoalSubmitEl) return;

            savingsGoalSubmitEl.disabled = true;
            savingsGoalErrorEl.classList.add('hidden');
            savingsGoalErrorEl.textContent = '';

            const formData = new FormData(savingsGoalFormEl);

            try {
                const response = await fetch(@json(route('savings-goals.upsert')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                if (!response.ok) {
                    const payload = await response.json().catch(() => ({}));
                    const message = payload.message
                        ?? Object.values(payload.errors ?? {})?.flat()?.[0]
                        ?? 'Não foi possível salvar a meta.';
                    throw new Error(message);
                }

                closeSavingsGoalModal();
                await loadDashboard();
            } catch (error) {
                savingsGoalErrorEl.textContent = error.message;
                savingsGoalErrorEl.classList.remove('hidden');
            } finally {
                savingsGoalSubmitEl.disabled = false;
            }
        });

        loadDashboard();
    </script>
</x-app-layout>
