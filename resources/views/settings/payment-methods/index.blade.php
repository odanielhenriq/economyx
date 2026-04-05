<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Formas de pagamento</h2>
            <a href="{{ route('payment-methods.create') }}" class="px-3 py-1 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                + Nova forma
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
                                    <th class="px-3 py-2 text-right">Acoes</th>
                                </tr>
                            </thead>
                            <tbody id="payment-methods-table" class="divide-y">
                                <tr>
                                    <td class="px-3 py-6 text-center text-gray-500" colspan="3">
                                        Carregando formas de pagamento...
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
            const tableBody = document.getElementById('payment-methods-table');
            const emptyRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-gray-500" colspan="3">
                        Nenhuma forma de pagamento cadastrada.
                    </td>
                </tr>
            `;
            const errorRow = `
                <tr>
                    <td class="px-3 py-6 text-center text-red-600" colspan="3">
                        Erro ao carregar formas de pagamento.
                    </td>
                </tr>
            `;
            const editUrlTemplate = @json(route('payment-methods.edit', ['payment_method' => '__ID__']));
            const deleteUrlTemplate = @json(route('payment-methods.destroy', ['payment_method' => '__ID__']));
            const csrfToken = @json(csrf_token());
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));

            fetch('/api/payment-methods', {
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

                    tableBody.innerHTML = items.map((paymentMethod) => {
                        const editUrl = editUrlTemplate.replace('__ID__', paymentMethod.id);
                        const deleteUrl = deleteUrlTemplate.replace('__ID__', paymentMethod.id);

                        return `
                            <tr>
                                <td class="px-3 py-2">${escapeHtml(paymentMethod.name)}</td>
                                <td class="px-3 py-2 text-gray-500">${escapeHtml(paymentMethod.slug)}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="${editUrl}" class="text-indigo-600 hover:underline">Editar</a>
                                        <form method="POST" action="${deleteUrl}" onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this } }));">
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
