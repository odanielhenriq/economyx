<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Meus cartões"
            subtitle="Cadastre seus cartões de crédito para acompanhar faturas, limites e compras parceladas."
        >
            <x-slot:actions>
                <a href="{{ route('credit-cards.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="hidden sm:inline">Cadastrar cartão</span>
                    <span class="sm:hidden">Novo cartão</span>
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        <x-flash-messages />

        <x-info-tip>
            O <strong>fechamento</strong> é o dia em que a fatura encerra; o <strong>vencimento</strong> é quando você precisa pagar. Cartões compartilhados aparecem para todos os parceiros da rede.
        </x-info-tip>

        <x-list-loading id="credit-cards-loading">Carregando cartões…</x-list-loading>

        <div id="credit-cards-empty" class="hidden"></div>
        <div id="credit-cards-error" class="hidden"></div>

        <div id="credit-cards-desktop" class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Dono</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" title="Dia do mês em que a fatura fecha">Fechamento</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" title="Dia do mês em que a fatura vence">Vencimento</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Limite</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Compartilhado</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Usuários</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="credit-cards-table" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <div id="credit-cards-mobile" class="hidden md:hidden space-y-3"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('credit-cards-table');
            const mobileList = document.getElementById('credit-cards-mobile');
            const emptyEl = document.getElementById('credit-cards-empty');
            const desktopEl = document.getElementById('credit-cards-desktop');
            const editUrlTemplate = @json(route('credit-cards.edit', ['credit_card' => '__ID__']));
            const deleteUrlTemplate = @json(route('credit-cards.destroy', ['credit_card' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            const deleteForm = (deleteUrl, name) => `
                <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this, title: 'Excluir cartão?', message: 'Essa ação não pode ser desfeita. O cartão será removido permanentemente.', itemName: '${name}', confirmLabel: 'Excluir cartão' } }));">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="text-sm font-medium text-red-600 hover:text-red-800" type="submit">Excluir</button>
                </form>`;

            const renderCard = (card) => {
                const editUrl = editUrlTemplate.replace('__ID__', card.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', card.id);
                const ownerLabel = escapeHtml(card.owner?.name ?? card.owner_name ?? '-');
                const name = escapeHtml(card.name);
                const limitLabel = card.limit
                    ? `R$ ${Number(card.limit).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                    : '—';
                const sharedLabel = card.is_shared ? 'Sim' : 'Não';
                const usersLabel = card.users?.length ? escapeHtml(card.users.map((u) => u.name).join(', ')) : '—';

                return { name, editUrl, deleteUrl, ownerLabel, limitLabel, sharedLabel, usersLabel, card };
            };

            const loadingEl = document.getElementById('credit-cards-loading');
            const errorEl = document.getElementById('credit-cards-error');

            const hideAll = () => {
                loadingEl?.classList.add('hidden');
                desktopEl.classList.add('hidden');
                mobileList.classList.add('hidden');
                emptyEl.classList.add('hidden');
                errorEl.classList.add('hidden');
            };

            fetch('/api/credit-cards', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    const items = payload.data ?? [];
                    hideAll();

                    if (!items.length) {
                        emptyEl.innerHTML = `
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                <h3 class="text-base font-semibold text-slate-900">Nenhum cartão cadastrado</h3>
                                <p class="mt-2 text-sm text-slate-500">Cadastre seu primeiro cartão para acompanhar faturas e compras parceladas.</p>
                                <a href="{{ route('credit-cards.create') }}" class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">Cadastrar cartão</a>
                            </div>`;
                        emptyEl.classList.remove('hidden');
                        return;
                    }

                    desktopEl.classList.remove('hidden');
                    mobileList.classList.remove('hidden');

                    tableBody.innerHTML = items.map((card) => {
                        const r = renderCard(card);
                        const aliasLabel = r.card.alias ? `<div class="text-xs text-slate-400">${escapeHtml(r.card.alias)}</div>` : '';
                        return `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><div class="font-medium text-slate-800">${r.name}</div>${aliasLabel}</td>
                                <td class="px-4 py-3 text-slate-600">${r.ownerLabel}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(r.card.closing_day ?? '—')}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(r.card.due_day ?? '—')}</td>
                                <td class="px-4 py-3 text-slate-600 tabular-nums">${r.limitLabel}</td>
                                <td class="px-4 py-3">${r.card.is_shared ? '<span class="text-emerald-700 font-medium">Sim</span>' : '<span class="text-slate-400">Não</span>'}</td>
                                <td class="px-4 py-3 text-slate-500">${r.usersLabel}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-3">
                                        <a href="${r.editUrl}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Editar</a>
                                        ${deleteForm(r.deleteUrl, r.name)}
                                    </div>
                                </td>
                            </tr>`;
                    }).join('');

                    mobileList.innerHTML = items.map((card) => {
                        const r = renderCard(card);
                        return `
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">${r.name}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">Dono: ${r.ownerLabel}</p>
                                    </div>
                                    <span class="text-xs font-medium ${r.card.is_shared ? 'text-emerald-700' : 'text-slate-400'}">${r.sharedLabel === 'Sim' ? 'Compartilhado' : 'Pessoal'}</span>
                                </div>
                                <dl class="grid grid-cols-2 gap-2 text-xs mb-3">
                                    <div><dt class="text-slate-400">Fechamento</dt><dd class="font-medium text-slate-700">${escapeHtml(r.card.closing_day ?? '—')}</dd></div>
                                    <div><dt class="text-slate-400">Vencimento</dt><dd class="font-medium text-slate-700">${escapeHtml(r.card.due_day ?? '—')}</dd></div>
                                    <div class="col-span-2"><dt class="text-slate-400">Limite</dt><dd class="font-medium text-slate-700 tabular-nums">${r.limitLabel}</dd></div>
                                </dl>
                                <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                                    <a href="${r.editUrl}" class="text-sm font-medium text-blue-600">Editar</a>
                                    ${deleteForm(r.deleteUrl, r.name)}
                                </div>
                            </div>`;
                    }).join('');
                })
                .catch(() => {
                    hideAll();
                    errorEl.innerHTML = `<div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center"><h3 class="text-base font-semibold text-slate-900">Erro ao carregar cartões</h3><p class="mt-2 text-sm text-slate-500">Tente recarregar a página.</p></div>`;
                    errorEl.classList.remove('hidden');
                });
        });
    </script>
</x-app-layout>
