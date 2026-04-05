<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">Tipos</h1>
            <a href="{{ route('types.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Novo tipo
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
                    <tbody id="types-table" class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="4">Carregando tipos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('types-table');
            const emptyRow = `<tr><td class="px-4 py-8 text-center text-slate-400" colspan="4">Nenhum tipo cadastrado.</td></tr>`;
            const errorRow = `<tr><td class="px-4 py-8 text-center text-red-500" colspan="4">Erro ao carregar tipos.</td></tr>`;
            const editUrlTemplate = @json(route('types.edit', ['type' => '__ID__']));
            const deleteUrlTemplate = @json(route('types.destroy', ['type' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            fetch('/api/types', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    const items = payload.data ?? [];
                    if (!items.length) { tableBody.innerHTML = emptyRow; return; }

                    tableBody.innerHTML = items.map((type) => {
                        const editUrl = editUrlTemplate.replace('__ID__', type.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', type.id);
                        const colorInfo = type.color
                            ? `<span class="inline-flex items-center gap-2">
                                <span class="inline-block w-3 h-3 rounded-sm" style="background-color: ${escapeHtml(type.color)}"></span>
                                <span class="text-slate-600 text-xs">${escapeHtml(type.color)}</span>
                               </span>`
                            : `<span class="text-slate-400">—</span>`;

                        return `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-800">${escapeHtml(type.name)}</td>
                                <td class="px-4 py-3 text-slate-500">${escapeHtml(type.slug)}</td>
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
