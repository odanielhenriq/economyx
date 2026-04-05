<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">Orçamentos por categoria</h1>
    </x-slot>

    <div class="max-w-3xl space-y-6">

        @if (session('success'))
            <div class="px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Formulário para definir / atualizar orçamento --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Definir orçamento</h3>
            <form method="POST" action="{{ route('budgets.store') }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div class="flex-1 min-w-40">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Categoria</label>
                    <select name="category_id" required
                        class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Limite mensal (R$)</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required
                        placeholder="0,00"
                        class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Salvar
                </button>
            </form>
        </div>

        {{-- Tabela de orçamentos existentes --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left divide-y divide-slate-100 min-w-[640px]">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoria</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Limite mensal</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($categories as $category)
                            @if ($budgets->has($category->id))
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $category->name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 tabular-nums">
                                        R$ {{ number_format($budgets[$category->id], 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
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
                                                <button type="submit"
                                                    class="text-sm font-medium text-red-600 hover:text-red-800">
                                                    Remover
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-400">
                                    Nenhuma categoria encontrada.
                                </td>
                            </tr>
                        @endforelse

                        @if ($budgets->isEmpty())
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-slate-400 text-xs">
                                    Nenhum orçamento definido ainda.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
