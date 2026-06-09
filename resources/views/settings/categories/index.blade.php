<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">Categorias</h1>
            <a href="{{ route('categories.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nova categoria
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
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-list-search id="categories-search" placeholder="Buscar categoria..." />

        {{-- Desktop: tabela --}}
        <div class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cor</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="categories-table" class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="3">Carregando categorias...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile: cards --}}
        <div id="categories-mobile" class="md:hidden space-y-3">
            <div class="text-center text-sm text-slate-400 py-8">Carregando categorias...</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[char]));

        const editUrlTemplate = @json(route('categories.edit', ['category' => '__ID__']));
        const deleteUrlTemplate = @json(route('categories.destroy', ['category' => '__ID__']));
        const csrfToken = @json(csrf_token());

        const actionsHtml = (editUrl, deleteUrl) => `
            <div class="flex justify-end gap-3">
                <a href="${editUrl}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                    Editar
                </a>
                <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this } }));">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="inline-flex items-center gap-1 text-sm font-medium text-red-600 hover:text-red-800" type="submit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79z" /></svg>
                        Excluir
                    </button>
                </form>
            </div>`;

        const colorHtml = (color) => color
            ? `<span class="inline-block w-4 h-4 rounded-full border border-slate-200" style="background-color: ${escapeHtml(color)}" title="${escapeHtml(color)}"></span>`
            : `<span class="text-slate-400 text-xs">Sem cor</span>`;

        window.initSearchableList({
            apiUrl: '/api/categories',
            tableBody: document.getElementById('categories-table'),
            mobileList: document.getElementById('categories-mobile'),
            searchInput: document.getElementById('categories-search'),
            emptyDesktopHtml: '<tr><td class="px-4 py-8 text-center text-slate-400" colspan="3">Nenhuma categoria encontrada.</td></tr>',
            emptyMobileHtml: '<div class="text-center text-sm text-slate-400 py-8">Nenhuma categoria encontrada.</div>',
            errorHtml: '<tr><td class="px-4 py-8 text-center text-red-500" colspan="3">Erro ao carregar categorias.</td></tr>',
            errorMobileHtml: '<div class="text-center text-sm text-red-500 py-8">Erro ao carregar categorias.</div>',
            renderTableRow: (category) => {
                const editUrl = editUrlTemplate.replace('__ID__', category.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', category.id);
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">${escapeHtml(category.name)}</td>
                        <td class="px-4 py-3">${colorHtml(category.color)}</td>
                        <td class="px-4 py-3 text-right">${actionsHtml(editUrl, deleteUrl)}</td>
                    </tr>`;
            },
            renderMobileCard: (category) => {
                const editUrl = editUrlTemplate.replace('__ID__', category.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', category.id);
                return `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <span class="font-medium text-slate-900">${escapeHtml(category.name)}</span>
                            ${colorHtml(category.color)}
                        </div>
                        ${actionsHtml(editUrl, deleteUrl)}
                    </div>`;
            },
        });
        });
    </script>
</x-app-layout>
