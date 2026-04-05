<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Orçamentos por categoria</h2>
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

            {{-- Formulário para definir / atualizar orçamento --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 border-b">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Definir orçamento</h3>
                    <form method="POST" action="{{ route('budgets.store') }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div class="flex-1 min-w-40">
                            <label class="block text-xs text-gray-600 mb-1">Categoria</label>
                            <select name="category_id" required
                                class="block w-full text-sm border-gray-300 rounded shadow-sm">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-40">
                            <label class="block text-xs text-gray-600 mb-1">Limite mensal (R$)</label>
                            <input type="number" name="amount" min="0.01" step="0.01" required
                                placeholder="0,00"
                                class="block w-full text-sm border-gray-300 rounded shadow-sm">
                        </div>
                        <button type="submit"
                            class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                            Salvar
                        </button>
                    </form>
                </div>
            </div>

            {{-- Tabela de orçamentos existentes --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="border-b text-gray-600">
                                <tr>
                                    <th class="px-3 py-2">Categoria</th>
                                    <th class="px-3 py-2 text-right">Limite mensal</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($categories as $category)
                                    @if ($budgets->has($category->id))
                                        <tr>
                                            <td class="px-3 py-2 font-medium">{{ $category->name }}</td>
                                            <td class="px-3 py-2 text-right">
                                                R$ {{ number_format($budgets[$category->id], 2, ',', '.') }}
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                @php
                                                    $budget = \App\Models\CategoryBudget::where('user_id', auth()->id())
                                                        ->where('category_id', $category->id)
                                                        ->first();
                                                @endphp
                                                @if ($budget)
                                                    <form method="POST"
                                                        action="{{ route('budgets.destroy', $budget) }}"
                                                        onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this } }));">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:underline">
                                                            Remover
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-6 text-center text-gray-500">
                                            Nenhuma categoria encontrada.
                                        </td>
                                    </tr>
                                @endforelse

                                @if ($budgets->isEmpty())
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-gray-400 text-xs">
                                            Nenhum orçamento definido ainda.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
