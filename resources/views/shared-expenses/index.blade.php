<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Gastos compartilhados"
            subtitle="Acompanhe os gastos divididos com seus parceiros e veja o que ainda falta acertar."
        />
    </x-slot>

    @php
        $summary = $data['summary'] ?? [];
        $filters = $data['filters'] ?? [];
    @endphp

    <div
        x-data="sharedExpensesPage"
        data-config='@json(['currentUserId' => $currentUserId, 'csrf' => csrf_token()])'
        class="space-y-6"
    >
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
                    const params = new URLSearchParams(window.location.search);
                    params.set('year', this.year);
                    params.set('month', this.month);
                    window.location.href = `{{ route('shared-expenses.index') }}?${params.toString()}`;
                }
            }"
            class="flex items-center justify-center gap-6"
        >
            <button type="button" @click="prev()" aria-label="Mês anterior"
                class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition">←</button>
            <span x-text="label" class="text-lg font-semibold text-slate-800 capitalize w-52 text-center" aria-live="polite"></span>
            <button type="button" @click="next()" aria-label="Próximo mês"
                class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition">→</button>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('shared-expenses.index') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="partner_id" class="block text-xs font-medium text-slate-500 mb-1">Parceiro</label>
                    <select id="partner_id" name="partner_id"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                        <option value="">Todos</option>
                        @foreach ($data['partners'] as $partner)
                            <option value="{{ $partner['user_id'] }}" @selected((string) ($filters['partner_id'] ?? '') === (string) $partner['user_id'])>
                                {{ $partner['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select id="status" name="status"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Todos</option>
                        <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pendentes</option>
                        <option value="settled" @selected(($filters['status'] ?? '') === 'settled')>Acertados</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                        Aplicar filtros
                    </button>
                </div>
            </div>
        </form>

        @if (! $data['has_partners'])
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Nenhum parceiro vinculado</p>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Convide alguém da sua casa para compartilhar gastos. Depois disso, eles aparecerão aqui.
                </p>
                <a href="{{ route('partners.index') }}"
                   class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    Gerenciar parceiros
                </a>
            </div>
        @elseif (! $data['has_shared_expenses'])
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Nenhum gasto compartilhado neste mês</p>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Quando você dividir uma transação com um parceiro, ela aparecerá aqui.
                </p>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    Adicionar transação compartilhada
                </a>
            </div>
        @else
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total compartilhado</p>
                    <p class="text-xl font-bold text-slate-900 tabular-nums mt-1">
                        R$ {{ number_format($summary['total_shared'] ?? 0, 2, ',', '.') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-4 bg-amber-50/40">
                    <p class="text-xs font-medium text-amber-700 uppercase tracking-wide">Pendente de acerto</p>
                    <p class="text-xl font-bold text-amber-800 tabular-nums mt-1">
                        R$ {{ number_format($summary['pending_settlement'] ?? 0, 2, ',', '.') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-4 bg-emerald-50/40">
                    <p class="text-xs font-medium text-emerald-700 uppercase tracking-wide">Já acertado</p>
                    <p class="text-xl font-bold text-emerald-800 tabular-nums mt-1">
                        R$ {{ number_format($summary['settled_total'] ?? 0, 2, ',', '.') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Gastos no mês</p>
                    <p class="text-xl font-bold text-slate-900 tabular-nums mt-1">{{ $summary['transactions_count'] ?? 0 }}</p>
                </div>
            </div>

            @if (count($data['suggestions'] ?? []) > 0)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2">Pendências de acerto</h3>
                    <div class="space-y-2">
                        @foreach ($data['suggestions'] as $suggestion)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                {{ $suggestion['message'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                @foreach ($data['expenses'] as $expense)
                    @php
                        $statusBadge = match ($expense['status']) {
                            'settled' => ['Acertado', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            'partial' => ['Parcial', 'bg-amber-50 text-amber-700 border-amber-200'],
                            default => ['Pendente', 'bg-red-50 text-red-700 border-red-200'],
                        };
                        $debtors = collect($expense['participants'])->where('settlement_role', 'debtor')->values();
                        $actionableDebtors = $debtors->filter(fn ($p) => $p['can_mark_settled'] || $p['can_unsettle'])->values();
                    @endphp
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <h3 class="text-sm font-semibold text-slate-800">{{ $expense['description'] }}</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-full border {{ $statusBadge[1] }}">
                                        {{ $statusBadge[0] }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ $expense['due_date'] ? \Carbon\Carbon::parse($expense['due_date'])->format('d/m/Y') : '—' }}
                                    · Total R$ {{ number_format($expense['amount'], 2, ',', '.') }}
                                    @if ($expense['payer'])
                                        · Pagou: <strong class="text-slate-700">{{ $expense['payer']['name'] }}</strong>
                                    @endif
                                </p>
                                @if ($expense['credit_card'])
                                    <p class="text-[11px] text-slate-400 mt-1">Cartão: {{ $expense['credit_card']['name'] }}</p>
                                @endif

                                <div class="mt-4 space-y-2">
                                    @foreach ($expense['participants'] as $participant)
                                        @php
                                            $participantBadge = match ($participant['settlement_status']) {
                                                'settled' => ['Acertada', 'text-emerald-700 bg-emerald-50 border-emerald-200'],
                                                'pending' => ['Pendente', 'text-amber-700 bg-amber-50 border-amber-200'],
                                                default => ['Quem pagou', 'text-slate-600 bg-slate-50 border-slate-200'],
                                            };
                                        @endphp
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-slate-800">{{ $participant['name'] }}</p>
                                                <p class="text-xs text-slate-500">
                                                    Parte: R$ {{ number_format($participant['share'], 2, ',', '.') }}
                                                    @if ($participant['settled_to'])
                                                        · deve para {{ $participant['settled_to']['name'] }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="inline-flex items-center self-start px-2 py-0.5 text-[11px] font-medium rounded-full border {{ $participantBadge[1] }}">
                                                {{ $participantBadge[0] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 shrink-0">
                                @if ($actionableDebtors->where('can_mark_settled', true)->isNotEmpty())
                                    @if ($actionableDebtors->where('can_mark_settled', true)->count() === 1)
                                        @php $target = $actionableDebtors->firstWhere('can_mark_settled', true); @endphp
                                        <button type="button"
                                            @click="openSettleModal(@js([
                                                'transactionId' => $expense['transaction_id'],
                                                'participantId' => $target['user_id'],
                                                'participantName' => $target['name'],
                                                'amount' => $target['share'],
                                                'payerName' => $expense['payer']['name'] ?? '',
                                                'description' => $expense['description'],
                                                'action' => 'settle',
                                            ]))"
                                            class="px-3 py-2 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                                            Marcar parte como acertada
                                        </button>
                                    @else
                                        <button type="button"
                                            @click="openChooseModal(@js([
                                                'transactionId' => $expense['transaction_id'],
                                                'description' => $expense['description'],
                                                'payerName' => $expense['payer']['name'] ?? '',
                                                'participants' => $actionableDebtors->where('can_mark_settled', true)->values()->all(),
                                                'action' => 'settle',
                                            ]))"
                                            class="px-3 py-2 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                                            Marcar parte como acertada
                                        </button>
                                    @endif
                                @endif

                                @if ($actionableDebtors->where('can_unsettle', true)->isNotEmpty())
                                    @if ($actionableDebtors->where('can_unsettle', true)->count() === 1)
                                        @php $target = $actionableDebtors->firstWhere('can_unsettle', true); @endphp
                                        <button type="button"
                                            @click="openSettleModal(@js([
                                                'transactionId' => $expense['transaction_id'],
                                                'participantId' => $target['user_id'],
                                                'participantName' => $target['name'],
                                                'amount' => $target['share'],
                                                'payerName' => $expense['payer']['name'] ?? '',
                                                'description' => $expense['description'],
                                                'action' => 'unsettle',
                                            ]))"
                                            class="px-3 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition">
                                            Desfazer acerto
                                        </button>
                                    @else
                                        <button type="button"
                                            @click="openChooseModal(@js([
                                                'transactionId' => $expense['transaction_id'],
                                                'description' => $expense['description'],
                                                'payerName' => $expense['payer']['name'] ?? '',
                                                'participants' => $actionableDebtors->where('can_unsettle', true)->values()->all(),
                                                'action' => 'unsettle',
                                            ]))"
                                            class="px-3 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition">
                                            Desfazer acerto
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-[11px] text-slate-400 text-center px-4">{{ $data['payer_note'] }}</p>
        @endif

        {{-- Modal escolher participante --}}
        <div x-show="chooseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40" @click="closeModals()"></div>
            <div class="relative w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-xl p-5">
                <h3 class="text-sm font-semibold text-slate-800">Escolha qual parte acertar</h3>
                <p class="text-xs text-slate-500 mt-1">Selecione a parte deste gasto que deseja marcar.</p>
                <div class="mt-4 space-y-2">
                    <template x-for="participant in chooseParticipants" :key="participant.user_id">
                        <button type="button"
                            @click="confirmChoose(participant)"
                            class="w-full text-left px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm">
                            <span x-text="participant.name"></span>
                            <span class="text-slate-400"> · R$ </span>
                            <span x-text="formatMoney(participant.share)"></span>
                        </button>
                    </template>
                </div>
                <button type="button" @click="closeModals()"
                    class="mt-4 w-full px-3 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">
                    Cancelar
                </button>
            </div>
        </div>

        {{-- Modal confirmar --}}
        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40" @click="closeModals()"></div>
            <div class="relative w-full max-w-md bg-white rounded-xl border border-slate-200 shadow-xl p-5">
                <h3 class="text-sm font-semibold text-slate-800" x-text="confirmTitle"></h3>
                <p class="text-sm text-slate-600 mt-2" x-text="confirmMessage"></p>
                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="closeModals()"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">
                        Cancelar
                    </button>
                    <button type="button" @click="submitSettlement()" :disabled="loading"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-60">
                        <span x-text="confirmActionLabel"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
