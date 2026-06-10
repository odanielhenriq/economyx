<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Contas fixas"
            subtitle="Cadastre despesas que se repetem todo mês — aluguel, assinaturas, planos de saúde e similares."
        >
            <x-slot:actions>
                <a href="{{ route('recurring-templates.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nova conta fixa
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        <x-flash-messages />

        <x-info-tip>
            Contas fixas aparecem automaticamente no dashboard quando chega o mês. Você também pode registrá-las manualmente como transação.
        </x-info-tip>

        <div id="recurring-loading" class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-12 text-center text-sm text-slate-400">
            Carregando contas fixas…
        </div>

        <div id="recurring-container" class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-slate-100 min-w-[640px]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Valor</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Recorrência</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pagamento</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="recurring-templates-table" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <div id="recurring-mobile" class="hidden md:hidden space-y-3"></div>
        <div id="recurring-empty" class="hidden"></div>
        <div id="recurring-error" class="hidden"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('recurring-templates-table');
            const container = document.getElementById('recurring-container');
            const mobileList = document.getElementById('recurring-mobile');
            const loadingEl = document.getElementById('recurring-loading');
            const emptyEl = document.getElementById('recurring-empty');
            const errorEl = document.getElementById('recurring-error');
            const emptyHtml = `
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">Nenhuma conta fixa cadastrada</h3>
                    <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">Cadastre aluguel, assinaturas e outras despesas que se repetem todo mês.</p>
                    <a href="{{ route('recurring-templates.create') }}"
                       class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                        Cadastrar conta fixa
                    </a>
                </div>`;
            const errorHtml = `
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center">
                    <h3 class="text-base font-semibold text-slate-900">Não foi possível carregar as contas fixas</h3>
                    <p class="mt-2 text-sm text-slate-500">Verifique sua conexão e tente recarregar a página.</p>
                    <button type="button" onclick="location.reload()"
                        class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        Tentar novamente
                    </button>
                </div>`;
            const editUrlTemplate = @json(route('recurring-templates.edit', ['recurring_template' => '__ID__']));
            const deleteUrlTemplate = @json(route('recurring-templates.destroy', ['recurring_template' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            const hideAll = () => {
                loadingEl.classList.add('hidden');
                container.classList.add('hidden');
                mobileList.classList.add('hidden');
                emptyEl.classList.add('hidden');
                errorEl.classList.add('hidden');
            };

            fetch('/api/recurring-templates', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    const items = payload.data ?? [];
                    hideAll();

                    if (!items.length) {
                        emptyEl.innerHTML = emptyHtml;
                        emptyEl.classList.remove('hidden');
                        return;
                    }

                    container.classList.remove('hidden');
                    mobileList.classList.remove('hidden');

                    tableBody.innerHTML = items.map((template) => {
                        const editUrl = editUrlTemplate.replace('__ID__', template.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', template.id);
                        const frequencia = template.frequency === 'yearly' ? 'Anual' : 'Mensal';
                        const frequencyLabel = `${frequencia} (dia ${template.day_of_month ?? '-'})`;
                        const proximaOcorrencia = template.next_occurrence
                            ? `<span class="block text-xs text-slate-400 mt-0.5">Próxima: ${template.next_occurrence}</span>`
                            : '';
                        const statusLabel = template.is_active
                            ? '<span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-emerald-100 text-emerald-700">Ativa</span>'
                            : '<span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-slate-100 text-slate-500">Inativa</span>';
                        const amountLabel = Number(template.amount ?? 0).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2, maximumFractionDigits: 2
                        });
                        const rowClass = template.is_active
                            ? 'hover:bg-slate-50 transition'
                            : 'opacity-50 bg-slate-50 hover:bg-slate-100 transition';

                        return `
                            <tr class="${rowClass}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">${escapeHtml(template.description)}</div>
                                    <div class="text-xs text-slate-400">
                                        Início: ${escapeHtml(template.start_date ?? '-')} · Fim: ${escapeHtml(template.end_date ?? 'sem fim')}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700 tabular-nums">R$ ${amountLabel}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <span class="text-sm">${escapeHtml(frequencyLabel)}</span>
                                    ${proximaOcorrencia}
                                </td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(template.category?.name ?? '-')}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(template.type?.name ?? '-')}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(template.payment_method?.name ?? '-')}</td>
                                <td class="px-4 py-3">${statusLabel}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-3">
                                        <a href="${editUrl}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Editar</a>
                                        <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this, title: 'Excluir conta fixa?', message: 'Ela deixará de aparecer nos próximos meses.', itemName: '${escapeHtml(template.description)}', confirmLabel: 'Excluir conta fixa' } }));">
                                            <input type="hidden" name="_token" value="${csrfToken}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button class="text-sm font-medium text-red-600 hover:text-red-800" type="submit">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');

                    mobileList.innerHTML = items.map((template) => {
                        const editUrl = editUrlTemplate.replace('__ID__', template.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', template.id);
                        const desc = escapeHtml(template.description);
                        const amountLabel = Number(template.amount ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        return `
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 ${template.is_active ? '' : 'opacity-60'}">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <p class="font-semibold text-slate-900">${desc}</p>
                                    ${template.is_active ? '<span class="text-xs font-medium text-emerald-700">Ativa</span>' : '<span class="text-xs text-slate-400">Inativa</span>'}
                                </div>
                                <p class="text-lg font-bold text-slate-800 tabular-nums mb-2">R$ ${amountLabel}</p>
                                <p class="text-xs text-slate-500">${escapeHtml(template.category?.name ?? '—')} · ${escapeHtml(template.payment_method?.name ?? '—')}</p>
                                <div class="flex justify-end gap-3 mt-3 pt-2 border-t border-slate-100">
                                    <a href="${editUrl}" class="text-sm font-medium text-blue-600">Editar</a>
                                    <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this, title: 'Excluir conta fixa?', message: 'Ela deixará de aparecer nos próximos meses.', itemName: '${desc}', confirmLabel: 'Excluir conta fixa' } }));">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button class="text-sm font-medium text-red-600" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </div>`;
                    }).join('');
                })
                .catch(() => {
                    hideAll();
                    errorEl.innerHTML = errorHtml;
                    errorEl.classList.remove('hidden');
                });
        });
    </script>
</x-app-layout>
