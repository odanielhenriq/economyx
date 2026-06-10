<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Orçamentos por categoria"
            subtitle="Defina quanto pode gastar em cada categoria por mês. O dashboard avisa quando você se aproximar do limite."
        />
    </x-slot>

    <div class="space-y-6">
        <x-flash-messages />

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-1">Definir orçamento</h2>
            <p class="text-xs text-slate-400 mb-4">Escolha a categoria e o valor máximo que deseja gastar por mês nela.</p>
            <form method="POST" action="{{ route('budgets.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_11rem_auto] gap-4 items-end">
                @csrf
                <div>
                    <label for="budget-category" class="block text-xs font-medium text-slate-500 mb-1">Categoria</label>
                    <select id="budget-category" name="category_id" required
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-field-hint class="mt-1">Escolha em qual grupo de gastos quer definir o limite.</x-field-hint>
                </div>
                <div x-data="{ raw: 0 }">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Limite mensal (R$)</label>
                    <input type="text" inputmode="numeric" required
                        @input="raw = formatCurrency($event)"
                        placeholder="R$ 0,00"
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <input type="hidden" name="amount" :value="raw.toFixed(2)">
                    <x-field-hint class="mt-1">Defina quanto pretende gastar por mês nessa categoria. O dashboard avisa quando chegar perto.</x-field-hint>
                </div>
                <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Salvar orçamento
                </button>
            </form>
        </div>

        @if ($budgets->isEmpty())
            <x-empty-state
                title="Nenhum orçamento definido"
                description="Defina limites mensais por categoria para receber alertas no dashboard quando estiver perto do limite."
            />
        @else
            {{-- Desktop --}}
            <div class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoria</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Limite mensal</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($categories as $category)
                                @if ($budgets->has($category->id))
                                    @php
                                        $budget = \App\Models\CategoryBudget::where('user_id', auth()->id())
                                            ->where('category_id', $category->id)
                                            ->first();
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $category->name }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700 tabular-nums">
                                            R$ {{ number_format($budgets[$category->id], 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($budget)
                                                <form method="POST" action="{{ route('budgets.destroy', $budget) }}"
                                                    onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this, title: 'Remover orçamento?', message: 'O limite mensal desta categoria será removido.', itemName: @json($category->name), confirmLabel: 'Remover orçamento' } }));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Remover</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile --}}
            <div class="md:hidden space-y-3">
                @foreach ($categories as $category)
                    @if ($budgets->has($category->id))
                        @php
                            $budget = \App\Models\CategoryBudget::where('user_id', auth()->id())
                                ->where('category_id', $category->id)
                                ->first();
                        @endphp
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-slate-900">{{ $category->name }}</p>
                                <p class="text-lg font-bold text-slate-800 tabular-nums mt-1">R$ {{ number_format($budgets[$category->id], 2, ',', '.') }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">Limite mensal</p>
                            </div>
                            @if ($budget)
                                <form method="POST" action="{{ route('budgets.destroy', $budget) }}"
                                    onsubmit="event.preventDefault(); window.dispatchEvent(new CustomEvent('request-delete', { detail: { form: this, title: 'Remover orçamento?', message: 'O limite mensal desta categoria será removido.', itemName: @json($category->name), confirmLabel: 'Remover' } }));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-red-600 px-3 py-2">Remover</button>
                                </form>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
