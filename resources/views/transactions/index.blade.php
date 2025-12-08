{{-- resources/views/transactions/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Transações') }}
        </h2>
    </x-slot>

   <div class="py-8">
    <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">

                <!-- Estado das transações -->
                <div id="transactions-state" class="mb-4 text-sm text-gray-500"></div>

                <!-- Resumo -->
                <div id="transactions-summary" class="grid grid-cols-1 gap-4 mb-6 text-sm md:grid-cols-3">
                    <div class="px-4 py-3 border rounded-lg bg-green-50">
                        <div class="text-xs text-gray-500">Receitas (página)</div>
                        <div class="text-lg font-semibold text-emerald-700">
                            R$&nbsp;0,00
                        </div>
                    </div>
                    <div class="px-4 py-3 border rounded-lg bg-red-50">
                        <div class="text-xs text-gray-500">Despesas (página)</div>
                        <div class="text-lg font-semibold text-red-700">
                            -R$&nbsp;0,00
                        </div>
                    </div>
                    <div class="px-4 py-3 border rounded-lg bg-slate-50">
                        <div class="text-xs text-gray-500">Saldo (página)</div>
                        <div class="text-lg font-semibold text-emerald-700">
                            R$&nbsp;0,00
                        </div>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="border-b text-gray-600">
                            <tr>
                                <th class="px-3 py-2">Info</th>
                                <th class="px-3 py-2">Data</th>
                                <th class="px-3 py-2">Descrição</th>
                                <th class="px-3 py-2 text-right">Valor</th>
                                <th class="px-3 py-2 text-right">Cota p/ pessoa</th>
                                <th class="px-3 py-2">Pessoas</th>
                                <th class="px-3 py-2">Parcelas</th>
                            </tr>
                        </thead>
                        <tbody id="transactions-body" class="divide-y">
                            <!-- Linhas carregadas via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="flex items-center justify-between mt-4 text-sm text-gray-500">
                    <button id="prev-page" class="px-3 py-1 border rounded disabled:opacity-50" disabled="">
                        Anterior
                    </button>

                    <span id="pagination-info">Página 1 de 1</span>

                    <button id="next-page" class="px-3 py-1 border rounded disabled:opacity-50">
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
    const summaryEl = document.getElementById('transactions-summary');

    let currentPage = 1;
    const perPage = 10;

    async function loadTransactions(page = 1) {
        stateEl.textContent = 'Carregando transações...';

        try {
            const response = await fetch(`/api/transactions?per_page=${perPage}&page=${page}`);

            if (!response.ok) throw new Error('Erro ao carregar transações');

            const json = await response.json();
            const items = json.data ?? [];
            const meta = json.meta ?? null;
            const links = json.links ?? null;

            bodyEl.innerHTML = '';
            summaryEl.innerHTML = '';

            if (items.length === 0) {
                stateEl.textContent = 'Nenhuma transação encontrada.';
                paginationInfoEl.textContent = '';
                prevBtn.disabled = true;
                nextBtn.disabled = true;
                return;
            }

            stateEl.textContent = '';

            // ===== Resumo =====
            let totalIncome = 0;
            let totalExpense = 0;

            items.forEach(tx => {
                const signed = Number(tx.signed_amount);
                if (signed > 0) totalIncome += signed;
                if (signed < 0) totalExpense += signed;
            });

            const balance = totalIncome + totalExpense;

            summaryEl.innerHTML = `
                <div class="px-4 py-3 border rounded-lg bg-green-50">
                    <div class="text-xs text-gray-500">Receitas (página)</div>
                    <div class="text-lg font-semibold text-emerald-700">
                        ${totalIncome.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                    </div>
                </div>
                <div class="px-4 py-3 border rounded-lg bg-red-50">
                    <div class="text-xs text-gray-500">Despesas (página)</div>
                    <div class="text-lg font-semibold text-red-700">
                        ${totalExpense.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                    </div>
                </div>
                <div class="px-4 py-3 border rounded-lg bg-slate-50">
                    <div class="text-xs text-gray-500">Saldo (página)</div>
                    <div class="text-lg font-semibold ${balance >= 0 ? 'text-emerald-700' : 'text-red-700'}">
                        ${balance.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                    </div>
                </div>
            `;

            // ===== Tabela =====
            items.forEach(tx => {
                const tr = document.createElement('tr');
                const isNegative = Number(tx.signed_amount) < 0;
                const amountFormatted = Number(tx.signed_amount).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                });

                const perUserFormatted = (tx.totals?.per_user_share !== null && tx.totals?.per_user_share !== undefined)
                    ? Number(tx.totals.per_user_share).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                    : '-';

                // ===== Coluna Info (mais limpa) =====
                const infoBadges = `
                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-slate-100 text-slate-700">
                        ${tx.category?.name ?? '-'}
                    </span>
                    ${tx.payment_method?.name === 'Credit Card' && tx.credit_card?.name
                        ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-purple-100 text-purple-700">
                                ${tx.credit_card.name}
                           </span>` : ''}
                `;

                // ===== Coluna Pessoas =====
                const usersDetailHtml = (tx.users ?? []).map(u => {
                    const share = Number(u.share_amount ?? 0).toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                    });
                    return `
                        <div class="flex items-center justify-between gap-2">
                            <span>${u.name}</span>
                            <span class="text-xs text-gray-500">${share}</span>
                        </div>
                    `;
                }).join('');

                // ===== Coluna Parcelas =====
                const installmentLabel = tx.installments?.is_installment
                    ? `${tx.installments.label} · faltam ${tx.installments.remaining}`
                    : '-';

                tr.innerHTML = `
                    <td class="px-3 py-2 text-gray-700 align-top">
                        <div class="flex flex-wrap gap-1">${infoBadges}</div>
                    </td>
                    <td class="px-3 py-2 text-gray-700 align-top">${tx.date}</td>
                    <td class="px-3 py-2 text-gray-700 align-top">
                        <div class="font-medium">${tx.description ?? '(sem descrição)'}</div>
                    </td>
                    <td class="px-3 py-2 text-right align-top">
                        <span class="${isNegative ? 'text-red-600' : 'text-emerald-600'} font-semibold">
                            ${amountFormatted}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 align-top">${perUserFormatted}</td>
                    <td class="px-3 py-2 text-gray-700 align-top">${usersDetailHtml || '-'}</td>
                    <td class="px-3 py-2 text-gray-700 align-top">${installmentLabel}</td>
                `;

                bodyEl.appendChild(tr);
            });

            // ===== Paginação =====
            if (meta) {
                currentPage = meta.current_page;
                paginationInfoEl.textContent = `Página ${meta.current_page} de ${meta.last_page}`;
                prevBtn.disabled = !(links && links.prev);
                nextBtn.disabled = !(links && links.next);
            } else {
                paginationInfoEl.textContent = '';
                prevBtn.disabled = true;
                nextBtn.disabled = true;
            }

        } catch (error) {
            console.error(error);
            stateEl.textContent = 'Erro ao carregar transações.';
            bodyEl.innerHTML = '';
            summaryEl.innerHTML = '';
            paginationInfoEl.textContent = '';
            prevBtn.disabled = true;
            nextBtn.disabled = true;
        }
    }

    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) loadTransactions(currentPage - 1);
    });

    nextBtn.addEventListener('click', () => loadTransactions(currentPage + 1));

    loadTransactions();
</script>
</x-app-layout>
