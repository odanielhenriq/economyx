<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Cartoes</h2>
            <a href="{{ route('credit-cards.create') }}" class="px-3 py-1 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                + Novo cartao
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8 space-y-6">
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
                                    <th class="px-3 py-2">Dono</th>
                                    <th class="px-3 py-2">Fechamento</th>
                                    <th class="px-3 py-2">Vencimento</th>
                                    <th class="px-3 py-2">Limite</th>
                                    <th class="px-3 py-2">Compartilhado</th>
                                    <th class="px-3 py-2">Usuarios</th>
                                    <th class="px-3 py-2 text-right">Acoes</th>
                                </tr>
                            </thead>
                            <tbody id="credit-cards-table" class="divide-y">
                                <tr>
                                    <td class="px-3 py-6 text-center text-gray-500" colspan="8">
                                        Carregando cartoes...
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
            const tableBody = document.getElementById('credit-cards-table');
            const emptyRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-gray-500" colspan="8">
                        Nenhum cartao cadastrado.
                    </td>
                </tr>
            `;
            const errorRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-red-600" colspan="8">
                        Erro ao carregar cartoes.
                    </td>
                </tr>
            `;
            const editUrlTemplate = @json(route('credit-cards.edit', ['credit_card' => '__ID__']));
            const deleteUrlTemplate = @json(route('credit-cards.destroy', ['credit_card' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));

            fetch('/api/credit-cards', {
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

                    tableBody.innerHTML = items.map((card) => {
                        const editUrl = editUrlTemplate.replace('__ID__', card.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', card.id);
                        const ownerLabel = card.owner?.name ?? card.owner_name ?? '-';
                        const limitLabel = card.limit
                            ? `R$ ${Number(card.limit).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                            : '-';
                        const sharedLabel = card.is_shared
                            ? '<span class="text-green-700">Sim</span>'
                            : '<span class="text-gray-500">Nao</span>';
                        const usersLabel = card.users?.length
                            ? escapeHtml(card.users.map((user) => user.name).join(', '))
                            : '-';
                        const aliasLabel = card.alias
                            ? `<div class="text-xs text-gray-500">${escapeHtml(card.alias)}</div>`
                            : '';

                        return `
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-medium">${escapeHtml(card.name)}</div>
                                    ${aliasLabel}
                                </td>
                                <td class="px-3 py-2 text-gray-600">${escapeHtml(ownerLabel)}</td>
                                <td class="px-3 py-2">${escapeHtml(card.closing_day ?? '-')}</td>
                                <td class="px-3 py-2">${escapeHtml(card.due_day ?? '-')}</td>
                                <td class="px-3 py-2">${limitLabel}</td>
                                <td class="px-3 py-2">${sharedLabel}</td>
                                <td class="px-3 py-2 text-gray-600">${usersLabel}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="${editUrl}" class="text-indigo-600 hover:underline">Editar</a>
                                        <form method="POST" action="${deleteUrl}" onsubmit="return confirm('Remover este cartao?');">
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
