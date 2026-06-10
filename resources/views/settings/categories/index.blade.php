<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Categorias"
            subtitle="Organize seus gastos e receitas em grupos — mercado, transporte, lazer e outros."
        >
            <x-slot:actions>
                <a href="{{ route('categories.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nova categoria
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        <x-flash-messages />

        <x-info-tip>
            Use categorias para agrupar gastos no dashboard e nos relatórios. Você pode editar ou excluir as que criou.
        </x-info-tip>

        <x-list-search id="categories-search" placeholder="Buscar categoria..." />

        <x-list-loading id="categories-loading">Carregando categorias…</x-list-loading>

        {{-- Desktop: tabela --}}
        <div id="categories-desktop" class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="categories-table" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <div id="categories-mobile" class="hidden md:hidden space-y-3"></div>
        <div id="categories-empty" class="hidden"></div>
        <div id="categories-error" class="hidden"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[char]));

        const editUrlTemplate = @json(route('categories.edit', ['category' => '__ID__']));
        const deleteUrlTemplate = @json(route('categories.destroy', ['category' => '__ID__']));
        const csrfToken = @json(csrf_token());

        const actionsHtml = (editUrl, deleteUrl, itemName) => `
            <div class="flex justify-end gap-3">
                <a href="${editUrl}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                    Editar
                </a>
                <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this, title: 'Excluir categoria?', message: 'Essa ação não pode ser desfeita. A categoria será removida permanentemente.', itemName: '${escapeHtml(itemName)}', confirmLabel: 'Excluir categoria' } }));">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button class="inline-flex items-center gap-1 text-sm font-medium text-red-600 hover:text-red-800" type="submit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79z" /></svg>
                        Excluir
                    </button>
                </form>
            </div>`;

        const nameHtml = (category) => category.color
            ? `<span class="inline-flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-full border border-slate-200 shrink-0" style="background-color: ${escapeHtml(category.color)}"></span>${escapeHtml(category.name)}</span>`
            : escapeHtml(category.name);

        window.initSearchableList({
            apiUrl: '/api/categories',
            tableBody: document.getElementById('categories-table'),
            mobileList: document.getElementById('categories-mobile'),
            searchInput: document.getElementById('categories-search'),
            loadingEl: document.getElementById('categories-loading'),
            desktopContainer: document.getElementById('categories-desktop'),
            mobileContainer: document.getElementById('categories-mobile'),
            emptyContainer: document.getElementById('categories-empty'),
            emptyHtml: `<div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center"><h3 class="text-base font-semibold text-slate-900">Nenhuma categoria encontrada</h3><p class="mt-2 text-sm text-slate-500">Cadastre categorias para organizar seus gastos.</p><a href="{{ route('categories.create') }}" class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">Nova categoria</a></div>`,
            errorContainer: document.getElementById('categories-error'),
            errorHtml: `<div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center"><h3 class="text-base font-semibold text-slate-900">Erro ao carregar categorias</h3><p class="mt-2 text-sm text-slate-500">Tente recarregar a página.</p></div>`,
            emptyDesktopHtml: '<tr><td class="px-4 py-8 text-center text-slate-400" colspan="2">Nenhuma categoria corresponde à busca.</td></tr>',
            emptyMobileHtml: '<div class="text-center text-sm text-slate-400 py-8">Nenhuma categoria encontrada.</div>',
            errorHtml: '<tr><td class="px-4 py-8 text-center text-red-500" colspan="2">Erro ao carregar categorias.</td></tr>',
            errorMobileHtml: '<div class="text-center text-sm text-red-500 py-8">Erro ao carregar categorias.</div>',
            renderTableRow: (category) => {
                const editUrl = editUrlTemplate.replace('__ID__', category.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', category.id);
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">${nameHtml(category)}</td>
                        <td class="px-4 py-3 text-right">${actionsHtml(editUrl, deleteUrl, category.name)}</td>
                    </tr>`;
            },
            renderMobileCard: (category) => {
                const editUrl = editUrlTemplate.replace('__ID__', category.id);
                const deleteUrl = deleteUrlTemplate.replace('__ID__', category.id);
                return `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                        <p class="font-medium text-slate-900 mb-3">${nameHtml(category)}</p>
                        ${actionsHtml(editUrl, deleteUrl, category.name)}
                    </div>`;
            },
        });
        });
    </script>
</x-app-layout>
