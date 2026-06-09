<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">Tipos de lançamento</h1>
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
                {{ $errors->first() }}
            </div>
        @endif

        <div class="px-4 py-3 text-sm text-slate-600 bg-slate-50 border border-slate-200 rounded-xl">
            <strong>Receita</strong> e <strong>Despesa</strong> são tipos do sistema usados nos lançamentos. Eles não podem ser alterados ou excluídos.
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Uso</th>
                        </tr>
                    </thead>
                    <tbody id="types-table" class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="2">Carregando tipos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('types-table');
            const systemSlugs = @json(\App\Support\ReferenceSlugs::systemTypeSlugs());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            fetch('/api/types', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    const items = payload.data ?? [];
                    if (!items.length) {
                        tableBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-slate-400" colspan="2">Nenhum tipo encontrado.</td></tr>';
                        return;
                    }

                    tableBody.innerHTML = items.map((type) => {
                        const isSystem = systemSlugs.includes(type.slug);
                        return `
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-800">${escapeHtml(type.name)}</td>
                                <td class="px-4 py-3 text-slate-500 text-sm">
                                    ${isSystem
                                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs">Tipo do sistema</span>'
                                        : escapeHtml(type.name)}
                                </td>
                            </tr>
                        `;
                    }).join('');
                })
                .catch(() => {
                    tableBody.innerHTML = '<tr><td class="px-4 py-8 text-center text-red-500" colspan="2">Erro ao carregar tipos.</td></tr>';
                });
        });
    </script>
</x-app-layout>
