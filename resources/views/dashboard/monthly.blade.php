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

    @php
        $formatMoney = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8 space-y-8">
            {{-- Cards de resumo --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">Receitas do mês</div>
                    <div class="text-lg font-semibold text-emerald-700">
                        {{ $formatMoney($cards['income_total_month']) }}
                    </div>
                </div>
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">Despesas do mês</div>
                    <div class="text-lg font-semibold text-red-600">
                        {{ $formatMoney($cards['expense_total_month']) }}
                    </div>
                </div>
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">Saldo do mês</div>
                    @php $balance = $cards['balance_month']; @endphp
                    <div class="text-lg font-semibold {{ $balance < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                        {{ $formatMoney($balance) }}
                    </div>
                </div>
                <div class="p-4 bg-white border rounded shadow-sm">
                    <div class="text-xs text-gray-500">A pagar no mês</div>
                    <div class="text-lg font-semibold text-gray-800">
                        {{ $formatMoney($cards['payable_total_month']) }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                        Cartões: {{ $formatMoney($cards['breakdown']['payable_cards_total']) }} ·
                        Empréstimos: {{ $formatMoney($cards['breakdown']['payable_loans_total']) }}
                    </div>
                </div>
            </div>

            {{-- A pagar no mês --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="bg-white border rounded shadow-sm">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">A pagar no mês — Cartões</h3>
                    </div>
                    <div class="p-4 space-y-3 text-sm">
                        @forelse ($lists['payables_cards'] as $card)
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-800">
                                        {{ $card['card_name'] ?? 'Cartão' }}
                                        @if (!empty($card['owner_name']))
                                            <span class="text-xs text-gray-500">({{ $card['owner_name'] }})</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">Vencimento: {{ $card['due_date'] }}</div>
                                </div>
                                <div class="font-semibold text-red-600">{{ $formatMoney($card['total']) }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500">Nenhuma fatura encontrada para este mês.</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border rounded shadow-sm">
                    <div class="px-4 py-3 border-b">
                        <h3 class="text-sm font-semibold text-gray-700">A pagar no mês — Empréstimos</h3>
                    </div>
                    <div class="p-4 space-y-3 text-sm">
                        @forelse ($lists['payables_loans'] as $loan)
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-800">
                                        {{ $loan['description'] ?? 'Parcela' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Vencimento: {{ $loan['due_date'] ?? '-' }}
                                        @if (!empty($loan['installment_total']) && $loan['installment_total'] > 1)
                                            · {{ $loan['installment_number'] }}/{{ $loan['installment_total'] }}
                                        @endif
                                    </div>
                                </div>
                                <div class="font-semibold text-red-600">{{ $formatMoney($loan['amount']) }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500">Nenhuma parcela de empréstimo neste mês.</div>
                        @endforelse
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
                        <tbody class="divide-y">
                            @forelse ($lists['cashflow_items'] as $item)
                                @php
                                    $isProjection = ($item['source'] ?? '') === 'projection';
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $item['due_date'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-800">{{ $item['description'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        {{ $formatMoney($item['amount'] ?? 0) }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full {{ $isProjection ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $isProjection ? 'Previsto' : 'Real' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-xs text-gray-500">
                                        Nenhuma movimentação encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
