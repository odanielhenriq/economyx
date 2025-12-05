{{-- resources/views/transactions/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Transações') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    {{-- Filtros futuros aqui (mês, pessoa, tipo, etc) --}}

                    <div id="transactions-state" class="text-sm text-gray-500 mb-4">
                        Carregando transações...
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="border-b text-gray-600">
                                <tr>
                                    <th class="px-3 py-2">Data</th>
                                    <th class="px-3 py-2">Descrição</th>
                                    <th class="px-3 py-2 text-right">Valor</th>
                                    <th class="px-3 py-2 text-right">Cota p/ pessoa</th>
                                    <th class="px-3 py-2">Pessoas</th>
                                    <th class="px-3 py-2">Parcelas</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-body" class="divide-y"></tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
                        <button id="prev-page" class="px-3 py-1 border rounded disabled:opacity-50" disabled>
                            Anterior
                        </button>
                        <span id="pagination-info"></span>
                        <button id="next-page" class="px-3 py-1 border rounded disabled:opacity-50" disabled>
                            Próxima
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Script da página --}}
    <script type="module">
        const stateEl = document.getElementById('transactions-state');
        const bodyEl = document.getElementById('transactions-body');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const paginationInfoEl = document.getElementById('pagination-info');

        let currentPage = 1;
        const perPage = 10;

        async function loadTransactions(page = 1) {
            stateEl.textContent = 'Carregando transações...';

            try {
                const response = await fetch(`/api/transactions?per_page=${perPage}&page=${page}`);

                if (!response.ok) {
                    throw new Error('Erro ao carregar transações');
                }

                const json = await response.json();
                const items = json.data ?? [];
                const meta = json.meta ?? null;
                const links = json.links ?? null;

                // Limpa tabela
                bodyEl.innerHTML = '';

                if (items.length === 0) {
                    stateEl.textContent = 'Nenhuma transação encontrada.';
                    paginationInfoEl.textContent = '';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                    return;
                }

                stateEl.textContent = '';

                items.forEach(tx => {
                    const tr = document.createElement('tr');

                    const isNegative = Number(tx.signed_amount) < 0;
                    const amountFormatted = Number(tx.signed_amount).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                    });

                    const perUserFormatted = tx.totals?.per_user_share !== null ?
                        Number(tx.totals.per_user_share).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                        }) :
                        '-';

                    const installmentLabel = tx.installments?.is_installment ?
                        tx.installments.label :
                        '-';

                    const usersNames = (tx.users ?? []).map(u => u.name).join(', ');

                    tr.innerHTML = `
                        <td class="px-3 py-2 text-gray-700">${tx.date}</td>
                        <td class="px-3 py-2 text-gray-700">
                            <div class="font-medium">${tx.description ?? '(sem descrição)'}</div>
                            <div class="text-xs text-gray-500">
                                ${tx.category?.name ?? '-'} · ${tx.type?.name ?? '-'} · ${tx.payment_method?.name ?? '-'}
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <span class="${isNegative ? 'text-red-600' : 'text-emerald-600'} font-semibold">
                                ${amountFormatted}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right text-gray-700">
                            ${perUserFormatted}
                        </td>
                        <td class="px-3 py-2 text-gray-700">
                            ${usersNames || '-'}
                        </td>
                        <td class="px-3 py-2 text-gray-700">
                            ${installmentLabel}
                        </td>
                    `;

                    bodyEl.appendChild(tr);
                });

                if (meta) {
                    currentPage = meta.current_page;
                    paginationInfoEl.textContent = `Página ${meta.current_page} de ${meta.last_page}`;

                    const hasPrev = !!(links && links.prev);
                    const hasNext = !!(links && links.next);

                    prevBtn.disabled = !hasPrev;
                    nextBtn.disabled = !hasNext;
                } else {
                    paginationInfoEl.textContent = '';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                }

            } catch (error) {
                console.error(error);
                stateEl.textContent = 'Erro ao carregar transações.';
                bodyEl.innerHTML = '';
                paginationInfoEl.textContent = '';
                prevBtn.disabled = true;
                nextBtn.disabled = true;
            }
        }

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                loadTransactions(currentPage - 1);
            }
        });

        nextBtn.addEventListener('click', () => {
            loadTransactions(currentPage + 1);
        });

        loadTransactions();
    </script>
</x-app-layout>
