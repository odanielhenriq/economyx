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

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Slug</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cor</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="categories-table" class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="4">
                                Carregando categorias...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('categories-table');
            const emptyRow = `
                <tr>
                    <td class="px-4 py-8 text-center text-slate-400" colspan="4">
                        Nenhuma categoria cadastrada.
                    </td>
                </tr>
            `;
            const errorRow = `
                <tr>
                    <td class="px-4 py-8 text-center text-red-500" colspan="4">
                        Erro ao carregar categorias.
                    </td>
                </tr>
            `;
            const editUrlTemplate = @json(route('categories.edit', ['category' => '__ID__']));
            const deleteUrlTemplate = @json(route('categories.destroy', ['category' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            fetch('/api/categories', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    const items = payload.data ?? [];
                    if (!items.length) { tableBody.innerHTML = emptyRow; return; }

                    tableBody.innerHTML = items.map((category) => {
                        const editUrl = editUrlTemplate.replace('__ID__', category.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', category.id);
                        const colorInfo = category.color
                            ? `<span class="inline-flex items-center gap-2">
                                <span class="inline-block w-3 h-3 rounded-sm" style="background-color: ${escapeHtml(category.color)}"></span>
                                <span class="text-slate-600 text-xs">${escapeHtml(category.color)}</span>
                               </span>`
                            : `<span class="text-slate-400">—</span>`;

                        return `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-800">${escapeHtml(category.name)}</td>
                                <td class="px-4 py-3 text-slate-500">${escapeHtml(category.slug)}</td>
                                <td class="px-4 py-3">${colorInfo}</td>
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
