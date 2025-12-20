<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Templates recorrentes</h2>
            <a href="{{ route('recurring-templates.create') }}"
                class="px-3 py-1 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                + Novo template
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
                                    <th class="px-3 py-2">Descricao</th>
                                    <th class="px-3 py-2">Valor</th>
                                    <th class="px-3 py-2">Recorrencia</th>
                                    <th class="px-3 py-2">Categoria</th>
                                    <th class="px-3 py-2">Tipo</th>
                                    <th class="px-3 py-2">Pagamento</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2 text-right">Acoes</th>
                                </tr>
                            </thead>
                            <tbody id="recurring-templates-table" class="divide-y">
                                <tr>
                                    <td class="px-3 py-6 text-center text-gray-500" colspan="8">
                                        Carregando templates...
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
            const tableBody = document.getElementById('recurring-templates-table');
            const emptyRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-gray-500" colspan="8">
                        Nenhum template cadastrado.
                    </td>
                </tr>
            `;
            const errorRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-red-600" colspan="8">
                        Erro ao carregar templates.
                    </td>
                </tr>
            `;
            const editUrlTemplate = @json(route('recurring-templates.edit', ['recurring_template' => '__ID__']));
            const deleteUrlTemplate = @json(route('recurring-templates.destroy', ['recurring_template' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));

            fetch('/api/recurring-templates', {
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

                    tableBody.innerHTML = items.map((template) => {
                        const editUrl = editUrlTemplate.replace('__ID__', template.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', template.id);
                        const frequencyLabel = template.frequency === 'yearly'
                            ? `Anual (${template.day_of_month ?? '-'})`
                            : `Mensal (${template.day_of_month ?? '-'})`;
                        const statusLabel = template.is_active
                            ? '<span class="text-green-700">Ativo</span>'
                            : '<span class="text-gray-500">Inativo</span>';
                        const amountLabel = Number(template.amount ?? 0).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });

                        return `
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-medium">${escapeHtml(template.description)}</div>
                                    <div class="text-xs text-gray-500">
                                        Inicio: ${escapeHtml(template.start_date ?? '-')} | Fim: ${escapeHtml(template.end_date ?? '-')}
                                    </div>
                                </td>
                                <td class="px-3 py-2">R$ ${amountLabel}</td>
                                <td class="px-3 py-2">${escapeHtml(frequencyLabel)}</td>
                                <td class="px-3 py-2">${escapeHtml(template.category?.name ?? '-')}</td>
                                <td class="px-3 py-2">${escapeHtml(template.type?.name ?? '-')}</td>
                                <td class="px-3 py-2">${escapeHtml(template.payment_method?.name ?? '-')}</td>
                                <td class="px-3 py-2">${statusLabel}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="${editUrl}" class="text-indigo-600 hover:underline">Editar</a>
                                        <form method="POST" action="${deleteUrl}" onsubmit="return confirm('Remover este template?');">
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
