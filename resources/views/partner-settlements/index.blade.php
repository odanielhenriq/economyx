<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Acerto mensal"
            subtitle="Veja quanto cada pessoa pagou, quanto deveria pagar e quem deve acertar com quem."
        />
    </x-slot>

    <div class="space-y-6">
        <x-flash-messages />

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
                    window.location.href = `{{ route('partner-settlements.index') }}?year=${this.year}&month=${this.month}`;
                }
            }"
            class="flex items-center justify-center gap-6"
        >
            <button type="button" @click="prev()" aria-label="Mês anterior"
                class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition">
                ←
            </button>
            <span x-text="label" class="text-lg font-semibold text-slate-800 capitalize w-52 text-center" aria-live="polite"></span>
            <button type="button" @click="next()" aria-label="Próximo mês"
                class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition">
                →
            </button>
        </div>

        @if (! $settlement['has_partners'])
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Nenhum parceiro vinculado</p>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Convide alguém da sua casa para compartilhar gastos. Depois disso, o acerto mensal aparecerá aqui.
                </p>
                <a href="{{ route('partners.index') }}"
                   class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    Gerenciar parceiros
                </a>
            </div>
        @elseif (! $settlement['has_shared_expenses'])
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Nenhum gasto compartilhado neste mês</p>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Quando você lançar uma transação dividida com um parceiro, o acerto mensal aparecerá aqui.
                </p>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    Adicionar transação compartilhada
                </a>
            </div>
        @else
            {{-- Resumo --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total compartilhado</p>
                    <p class="text-2xl font-bold text-slate-900 tabular-nums mt-1">
                        R$ {{ number_format($settlement['total_shared'], 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ $settlement['month_label'] }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Gastos compartilhados</p>
                    <p class="text-2xl font-bold text-slate-900 tabular-nums mt-1">
                        {{ $settlement['transactions_count'] }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $settlement['transactions_count'] === 1 ? 'transação no mês' : 'transações no mês' }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pessoas envolvidas</p>
                    <p class="text-2xl font-bold text-slate-900 tabular-nums mt-1">
                        {{ $settlement['participants_count'] }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">com gastos divididos</p>
                </div>
            </div>

            {{-- Cards por pessoa --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($settlement['participants'] as $participant)
                    @php
                        $statusLabel = match ($participant['status']) {
                            'receives' => 'Tem a receber',
                            'owes' => 'Precisa pagar',
                            default => 'Acertado',
                        };
                        $statusClass = match ($participant['status']) {
                            'receives' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                            'owes' => 'text-red-700 bg-red-50 border-red-200',
                            default => 'text-slate-600 bg-slate-50 border-slate-200',
                        };
                        $balanceClass = match ($participant['status']) {
                            'receives' => 'text-emerald-700',
                            'owes' => 'text-red-600',
                            default => 'text-slate-700',
                        };
                    @endphp
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <h3 class="text-sm font-semibold text-slate-800">{{ $participant['name'] }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-full border {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div class="space-y-2 text-sm text-slate-600">
                            <div class="flex justify-between gap-4">
                                <span>Pagou</span>
                                <span class="font-medium text-slate-900 tabular-nums">
                                    R$ {{ number_format($participant['paid'], 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span>Parte dela</span>
                                <span class="font-medium text-slate-900 tabular-nums">
                                    R$ {{ number_format($participant['share'], 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="border-t border-slate-100 pt-2 flex justify-between gap-4 font-semibold">
                                <span>Saldo</span>
                                <span class="{{ $balanceClass }} tabular-nums">
                                    R$ {{ number_format(abs($participant['balance']), 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sugestões de acerto --}}
            @if (count($settlement['suggestions']) > 0)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-1">Sugestão de acerto</h3>
                    <p class="text-xs text-slate-400 mb-4">Valores calculados com base na divisão igualitária dos gastos compartilhados.</p>
                    <div class="space-y-3">
                        @foreach ($settlement['suggestions'] as $suggestion)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                {{ $suggestion['message'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="text-[11px] text-slate-400 text-center px-4">
                {{ $settlement['payer_note'] }}
            </p>
        @endif
    </div>
</x-app-layout>
