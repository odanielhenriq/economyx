<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Compras parceladas"
            subtitle="Acompanhe o que você ainda está pagando e veja quando cada compra termina."
        />
    </x-slot>

    @php
        $summary = $data['summary'] ?? [];
        $filters = $data['filters'] ?? [];
        $options = $data['options'] ?? [];
    @endphp

    <div class="space-y-6">
        <x-flash-messages />

        {{-- Filtros --}}
        <form method="GET" action="{{ route('installment-purchases.index') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label for="status" class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                    <select id="status" name="status" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                        <option value="active" @selected(($filters['status'] ?? 'active') === 'active')>Ativas</option>
                        <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Quitadas</option>
                        <option value="all" @selected(($filters['status'] ?? '') === 'all')>Todas</option>
                    </select>
                </div>
                <div>
                    <label for="credit_card_id" class="block text-xs font-medium text-slate-500 mb-1">Cartão</label>
                    <select id="credit_card_id" name="credit_card_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                        <option value="">Todos</option>
                        @foreach ($options['credit_cards'] ?? [] as $card)
                            <option value="{{ $card['id'] }}" @selected((string) ($filters['credit_card_id'] ?? '') === (string) $card['id'])>
                                {{ $card['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="category_id" class="block text-xs font-medium text-slate-500 mb-1">Categoria</label>
                    <select id="category_id" name="category_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                        <option value="">Todas</option>
                        @foreach ($options['categories'] ?? [] as $category)
                            <option value="{{ $category['id'] }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category['id'])>
                                {{ $category['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="purchase_from" class="block text-xs font-medium text-slate-500 mb-1">Compra de</label>
                    <input type="date" id="purchase_from" name="purchase_from" value="{{ $filters['purchase_from'] ?? '' }}"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                </div>
                <div>
                    <label for="purchase_to" class="block text-xs font-medium text-slate-500 mb-1">Compra até</label>
                    <input type="date" id="purchase_to" name="purchase_to" value="{{ $filters['purchase_to'] ?? '' }}"
                        class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                    Aplicar filtros
                </button>
            </div>
        </form>

        @if (! $data['has_items'] && ($filters['status'] ?? 'active') === 'active' && empty($filters['credit_card_id']) && empty($filters['category_id']) && empty($filters['purchase_from']) && empty($filters['purchase_to']))
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Nenhuma compra parcelada ativa</p>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Quando você lançar uma compra parcelada, ela aparecerá aqui com o progresso das parcelas.
                </p>
                <a href="{{ route('transactions.create') }}"
                   class="inline-flex items-center mt-4 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    Adicionar compra parcelada
                </a>
            </div>
        @elseif (! $data['has_items'])
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Nenhuma compra encontrada</p>
                <p class="text-xs text-slate-500 mt-1">Tente ajustar os filtros para ver outras compras parceladas.</p>
            </div>
        @else
            @if ($data['has_active_items'])
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Compras ativas</p>
                        <p class="text-xl font-bold text-slate-900 tabular-nums mt-1">{{ $summary['active_count'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-4 bg-amber-50/40">
                        <p class="text-xs font-medium text-amber-700 uppercase tracking-wide">Valor restante</p>
                        <p class="text-xl font-bold text-amber-800 tabular-nums mt-1">
                            R$ {{ number_format($summary['remaining_total'] ?? 0, 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Próxima parcela</p>
                        @if (! empty($summary['next_installment']['due_date']))
                            <p class="text-sm font-bold text-slate-900 mt-1">
                                {{ \Carbon\Carbon::parse($summary['next_installment']['due_date'])->format('d/m/Y') }}
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5 truncate">
                                {{ $summary['next_installment']['description'] }}
                                · R$ {{ number_format($summary['next_installment']['amount'] ?? 0, 2, ',', '.') }}
                            </p>
                        @else
                            <p class="text-sm font-medium text-slate-400 mt-1">—</p>
                        @endif
                    </div>
                    <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-4 bg-emerald-50/40">
                        <p class="text-xs font-medium text-emerald-700 uppercase tracking-wide">Finalizando</p>
                        <p class="text-xl font-bold text-emerald-800 tabular-nums mt-1">{{ $summary['ending_soon_count'] ?? 0 }}</p>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                @foreach ($data['items'] as $item)
                    @php
                        $statusBadge = match ($item['status']) {
                            'completed' => ['Quitada', 'bg-slate-50 text-slate-600 border-slate-200'],
                            'ending' => ['Finalizando', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            default => ['Em andamento', 'bg-blue-50 text-blue-700 border-blue-200'],
                        };
                        $progress = $item['total_installments'] > 0
                            ? min(100, round(($item['current_installment'] / $item['total_installments']) * 100))
                            : 0;
                    @endphp
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-slate-800">{{ $item['description'] }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-full border {{ $statusBadge[1] }}">
                                            {{ $statusBadge[0] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        Compra em {{ $item['purchase_date'] ? \Carbon\Carbon::parse($item['purchase_date'])->format('d/m/Y') : '—' }}
                                        @if ($item['card_name'])
                                            · Cartão {{ $item['card_name'] }}
                                        @endif
                                        @if ($item['category_name'])
                                            · {{ $item['category_name'] }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-left sm:text-right shrink-0">
                                    <p class="text-xs text-slate-500">Total</p>
                                    <p class="text-sm font-bold text-slate-900 tabular-nums">
                                        R$ {{ number_format($item['total_amount'], 2, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                                    <span>{{ $item['current_installment'] }} de {{ $item['total_installments'] }} parcelas</span>
                                    <span>{{ $item['remaining_installments'] }} restante(s)</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-green-500 transition-all" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                                <div>
                                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Parcela</p>
                                    <p class="font-semibold text-slate-800 tabular-nums mt-0.5">
                                        R$ {{ number_format($item['installment_amount'], 2, ',', '.') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Restante</p>
                                    <p class="font-semibold text-amber-700 tabular-nums mt-0.5">
                                        R$ {{ number_format($item['remaining_amount'], 2, ',', '.') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Próxima parcela</p>
                                    <p class="font-semibold text-slate-800 mt-0.5">
                                        @if ($item['next_due_date'])
                                            {{ \Carbon\Carbon::parse($item['next_due_date'])->format('d/m/Y') }}
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-slate-400 uppercase tracking-wide">Status</p>
                                    <p class="font-semibold text-slate-800 mt-0.5">{{ $item['status_label'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-[11px] text-slate-400 text-center px-4">{{ $data['payment_note'] }}</p>
        @endif
    </div>
</x-app-layout>
