<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">Cartões</h1>
            <a href="{{ route('credit-cards.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Novo cartão
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">

        @if (session('success'))
            <div class="px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Dono</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fechamento</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vencimento</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Limite</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Compartilhado</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Usuários</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="credit-cards-table" class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="8">Carregando cartões...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('credit-cards-table');
            const emptyRow = `<tr><td class="px-4 py-8 text-center text-slate-400" colspan="8">Nenhum cartão cadastrado.</td></tr>`;
            const errorRow = `<tr><td class="px-4 py-8 text-center text-red-500" colspan="8">Erro ao carregar cartões.</td></tr>`;
            const editUrlTemplate = @json(route('credit-cards.edit', ['credit_card' => '__ID__']));
            const deleteUrlTemplate = @json(route('credit-cards.destroy', ['credit_card' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            fetch('/api/credit-cards', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    const items = payload.data ?? [];
                    if (!items.length) { tableBody.innerHTML = emptyRow; return; }

                    tableBody.innerHTML = items.map((card) => {
                        const editUrl = editUrlTemplate.replace('__ID__', card.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', card.id);
                        const ownerLabel = card.owner?.name ?? card.owner_name ?? '-';
                        const limitLabel = card.limit
                            ? `R$ ${Number(card.limit).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                            : '-';
                        const sharedLabel = card.is_shared
                            ? '<span class="text-emerald-700 font-medium">Sim</span>'
                            : '<span class="text-slate-400">Não</span>';
                        const usersLabel = card.users?.length
                            ? escapeHtml(card.users.map((u) => u.name).join(', '))
                            : '-';
                        const aliasLabel = card.alias
                            ? `<div class="text-xs text-slate-400">${escapeHtml(card.alias)}</div>`
                            : '';

                        return `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">${escapeHtml(card.name)}</div>
                                    ${aliasLabel}
                                </td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(ownerLabel)}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(card.closing_day ?? '-')}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHtml(card.due_day ?? '-')}</td>
                                <td class="px-4 py-3 text-slate-600 tabular-nums">${limitLabel}</td>
                                <td class="px-4 py-3">${sharedLabel}</td>
                                <td class="px-4 py-3 text-slate-500">${usersLabel}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-3">
                                        <a href="${editUrl}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Editar</a>
                                        <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this } }));">
                                            <input type="hidden" name="_token" value="${csrfToken}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button class="text-sm font-medium text-red-600 hover:text-red-800" type="submit">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                })
                .catch(() => { tableBody.innerHTML = errorRow; });
        });
    </script>
</x-app-layout>
