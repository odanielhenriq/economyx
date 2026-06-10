<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Formas de pagamento"
            subtitle="Cadastre como você paga — Pix, cartão, boleto e outras opções usadas nas transações."
        >
            <x-slot:actions>
                <a href="{{ route('payment-methods.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nova forma de pagamento
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        <x-flash-messages />

        <x-list-search id="payment-methods-search" placeholder="Buscar forma de pagamento..." />

        <x-list-loading id="payment-methods-loading">Carregando formas de pagamento…</x-list-loading>

        <div id="payment-methods-desktop" class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="payment-methods-table" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <div id="payment-methods-mobile" class="hidden md:hidden space-y-3"></div>
        <div id="payment-methods-empty" class="hidden"></div>
        <div id="payment-methods-error" class="hidden"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[char]));

        const editUrlTemplate = @json(route('payment-methods.edit', ['payment_method' => '__ID__']));
        const deleteUrlTemplate = @json(route('payment-methods.destroy', ['payment_method' => '__ID__']));
        const csrfToken = @json(csrf_token());

        const actionsHtml = (editUrl, deleteUrl, itemName) => `
            <div class="flex justify-end gap-3">
                <a href="${editUrl}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">Editar</a>
                <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this, title: 'Excluir forma de pagamento?', message: 'Essa ação não pode ser desfeita.', itemName: '${escapeHtml(itemName)}', confirmLabel: 'Excluir forma' } }));">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="text-sm font-medium text-red-600 hover:text-red-800" type="submit">Excluir</button>
                </form>
            </div>`;

        window.initSearchableList({
            apiUrl: '/api/payment-methods',
            tableBody: document.getElementById('payment-methods-table'),
            mobileList: document.getElementById('payment-methods-mobile'),
            searchInput: document.getElementById('payment-methods-search'),
            loadingEl: document.getElementById('payment-methods-loading'),
            desktopContainer: document.getElementById('payment-methods-desktop'),
            mobileContainer: document.getElementById('payment-methods-mobile'),
            emptyContainer: document.getElementById('payment-methods-empty'),
            emptyHtml: `<div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center"><h3 class="text-base font-semibold text-slate-900">Nenhuma forma de pagamento</h3><p class="mt-2 text-sm text-slate-500">Cadastre Pix, dinheiro, boleto e outras opções.</p><a href="{{ route('payment-methods.create') }}" class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">Nova forma de pagamento</a></div>`,
            errorContainer: document.getElementById('payment-methods-error'),
            errorHtml: `<div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center"><h3 class="text-base font-semibold text-slate-900">Erro ao carregar</h3><p class="mt-2 text-sm text-slate-500">Tente recarregar a página.</p></div>`,
            emptyDesktopHtml: '<tr><td class="px-4 py-8 text-center text-slate-400" colspan="2">Nenhuma forma corresponde à busca.</td></tr>',
            emptyMobileHtml: '<div class="text-center text-sm text-slate-400 py-8">Nenhuma forma encontrada.</div>',
            errorHtml: '<tr><td class="px-4 py-8 text-center text-red-500" colspan="2">Erro ao carregar formas de pagamento.</td></tr>',
            errorMobileHtml: '<div class="text-center text-sm text-red-500 py-8">Erro ao carregar formas de pagamento.</div>',
            renderTableRow: (pm) => {
                const editUrl = editUrlTemplate.replace('__ID__', pm.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', pm.id);
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">${escapeHtml(pm.name)}</td>
                        <td class="px-4 py-3 text-right">${actionsHtml(editUrl, deleteUrl, pm.name)}</td>
                    </tr>`;
            },
            renderMobileCard: (pm) => {
                const editUrl = editUrlTemplate.replace('__ID__', pm.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', pm.id);
                return `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                        <p class="font-medium text-slate-900 mb-3">${escapeHtml(pm.name)}</p>
                        ${actionsHtml(editUrl, deleteUrl, pm.name)}
                    </div>`;
            },
        });
        });
    </script>
</x-app-layout>
