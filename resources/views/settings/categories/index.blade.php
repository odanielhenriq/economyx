<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Categorias</h2>
            <a href="{{ route('categories.create') }}" class="px-3 py-1 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                + Nova categoria
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">
            @include('settings.nav')

            @if (session('success'))
                <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="border-b text-gray-600">
                                <tr>
                                    <th class="px-3 py-2">Nome</th>
                                    <th class="px-3 py-2">Slug</th>
                                    <th class="px-3 py-2">Cor</th>
                                    <th class="px-3 py-2 text-right">Acoes</th>
                                </tr>
                            </thead>
                            <tbody id="categories-table" class="divide-y">
                                <tr>
                                    <td class="px-3 py-6 text-center text-gray-500" colspan="4">
                                        Carregando categorias...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('categories-table');
            const emptyRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-gray-500" colspan="4">
                        Nenhuma categoria cadastrada.
                    </td>
                </tr>
            `;
            const errorRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-red-600" colspan="4">
                        Erro ao carregar categorias.
                    </td>
                </tr>
            `;
            const editUrlTemplate = @json(route('categories.edit', ['category' => '__ID__']));
            const deleteUrlTemplate = @json(route('categories.destroy', ['category' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));

            fetch('/api/categories', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then((response) => response.ok ? response.json() : Promise.reject(response))
                .then((payload) => {
                    const items = payload.data ?? [];

                    if (!items.length) {
                        tableBody.innerHTML = emptyRow;
                        return;
                    }

                    tableBody.innerHTML = items.map((category) => {
                        const editUrl = editUrlTemplate.replace('__ID__', category.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', category.id);
                        const colorInfo = category.color
                            ? `
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-block w-3 h-3 rounded" style="background-color: ${escapeHtml(category.color)}"></span>
                                    <span class="text-gray-600">${escapeHtml(category.color)}</span>
                                </span>
                            `
                            : `<span class="text-gray-400">-</span>`;

                        return `
                            <tr>
                                <td class="px-3 py-2">${escapeHtml(category.name)}</td>
                                <td class="px-3 py-2 text-gray-500">${escapeHtml(category.slug)}</td>
                                <td class="px-3 py-2">${colorInfo}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="${editUrl}" class="text-indigo-600 hover:underline">Editar</a>
                                        <form method="POST" action="${deleteUrl}" onsubmit="return confirm('Remover esta categoria?');">
                                            <input type="hidden" name="_token" value="${csrfToken}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button class="text-red-600 hover:underline" type="submit">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                })
                .catch(() => {
                    tableBody.innerHTML = errorRow;
                });
        });
    </script>
</x-app-layout>
