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

                    {{-- Filtros --}}
                    <div class="flex flex-col gap-3 mb-4 md:flex-row md:items-end">
                        <div class="flex-1">
                            <label class="text-sm text-gray-600">Cartão</label>
                            <select id="filter-card" class="block w-full mt-1 text-sm border-gray-300 rounded">
                                @foreach ($cards as $card)
                                    <option value="{{ $card->id }}">
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

                    {{-- Estado --}}
                    <div id="card-state" class="text-sm text-gray-500">Escolha um cartão e um mês.</div>

                    {{-- Resumo da fatura --}}
                    <div id="card-summary" class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-3 text-sm">
                        {{-- preenchido via JS --}}
                    </div>

                    {{-- Período --}}
                    <div id="card-period" class="mt-2 text-xs text-gray-500">
                        {{-- preenchido via JS --}}
                    </div>

                    {{-- Tabela --}}
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
                                {{-- linhas via JS --}}
                            </tbody>
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
        const periodEl = document.getElementById('card-period');
        const bodyEl = document.getElementById('card-body');

        async function loadStatement(cardId, year, month) {
            try {
                const url = `/api/cards/${cardId}/statement?year=${year}&month=${month}`;
                console.log('URL usada:', url);

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error('Erro ao buscar fatura');
                }

                const data = await response.json();
                console.log('API DATA:', data);

                if (!Array.isArray(data.transactions)) {
                    console.error('transactions não é um array:', data.transactions);
                    return;
                }

                // limpa estado anterior
                stateEl.textContent = '';
                summaryEl.innerHTML = '';
                periodEl.textContent = '';
                bodyEl.innerHTML = '';

                // resumo
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

                // período
                periodEl.textContent = `Período da fatura: ${data.period.start} até ${data.period.end}`;

                // linhas da tabela
                data.transactions.forEach(tx => {
                    const tr = document.createElement('tr');

                    tr.innerHTML = `
                <td class="px-3 py-2 text-xs text-gray-500">${tx.date}</td>
                <td class="px-3 py-2">
                    <div class="font-medium">${tx.description}</div>
                    ${tx.installments.is_installment && tx.installments.label
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

                if (data.transactions.length === 0) {
                    stateEl.textContent = 'Nenhuma transação nessa fatura.';
                }

            } catch (error) {
                console.error('Erro em loadStatement:', error);
                stateEl.textContent = 'Erro ao carregar fatura. Tente novamente.';
            }
        }

        function handleLoadClick() {
            const cardId = cardSelect.value;

            const monthValue = monthInput.value; // tipo "2025-11"
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

            stateEl.textContent = 'Carregando fatura...';

            loadStatement(cardId, year, month);
        }

        loadBtn.addEventListener('click', handleLoadClick);

    </script>
</x-app-layout>
