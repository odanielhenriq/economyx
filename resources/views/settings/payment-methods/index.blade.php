<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">Formas de pagamento</h1>
            <a href="{{ route('payment-methods.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nova forma
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        @include('settings.nav')

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

        <x-list-search id="payment-methods-search" placeholder="Buscar forma de pagamento..." />

        <div class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="payment-methods-table" class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="2">Carregando formas de pagamento...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="payment-methods-mobile" class="md:hidden space-y-3">
            <div class="text-center text-sm text-slate-400 py-8">Carregando formas de pagamento...</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[char]));

        const editUrlTemplate = @json(route('payment-methods.edit', ['payment_method' => '__ID__']));
        const deleteUrlTemplate = @json(route('payment-methods.destroy', ['payment_method' => '__ID__']));
        const csrfToken = @json(csrf_token());

        const actionsHtml = (editUrl, deleteUrl) => `
            <div class="flex justify-end gap-3">
                <a href="${editUrl}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">Editar</a>
                <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this } }));">
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
            emptyDesktopHtml: '<tr><td class="px-4 py-8 text-center text-slate-400" colspan="2">Nenhuma forma encontrada.</td></tr>',
            emptyMobileHtml: '<div class="text-center text-sm text-slate-400 py-8">Nenhuma forma encontrada.</div>',
            errorHtml: '<tr><td class="px-4 py-8 text-center text-red-500" colspan="2">Erro ao carregar formas de pagamento.</td></tr>',
            errorMobileHtml: '<div class="text-center text-sm text-red-500 py-8">Erro ao carregar formas de pagamento.</div>',
            renderTableRow: (pm) => {
                const editUrl = editUrlTemplate.replace('__ID__', pm.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', pm.id);
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">${escapeHtml(pm.name)}</td>
                        <td class="px-4 py-3 text-right">${actionsHtml(editUrl, deleteUrl)}</td>
                    </tr>`;
            },
            renderMobileCard: (pm) => {
                const editUrl = editUrlTemplate.replace('__ID__', pm.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', pm.id);
                return `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                        <p class="font-medium text-slate-900 mb-3">${escapeHtml(pm.name)}</p>
                        ${actionsHtml(editUrl, deleteUrl)}
                    </div>`;
            },
        });
        });
    </script>
</x-app-layout>
