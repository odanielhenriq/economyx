<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Nova categoria"
            subtitle="Categorias ajudam a entender para onde seu dinheiro está indo — no dashboard, nas transações e nos orçamentos."
            back-href="{{ route('categories.index') }}"
            back-label="Voltar para categorias"
        />
    </x-slot>

    <div class="max-w-xl space-y-6">

        @if ($errors->any())
            <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
                <p class="font-medium mb-1">Corrija os campos abaixo:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('categories.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nome da categoria</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Ex: Mercado, Transporte, Lazer" required>
                    <x-field-hint>Escolha um nome que faça sentido para você ao filtrar gastos.</x-field-hint>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cor</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="color"
                            value="{{ old('color', '#16a34a') }}"
                            class="h-10 w-16 rounded-lg border border-slate-200 cursor-pointer p-1 bg-white">
                        <span class="text-sm text-slate-500">Aparece no gráfico e nas listas</span>
                    </div>
                </div>
            </div>

            <x-form-actions cancel-href="{{ route('categories.index') }}" submit-label="Salvar categoria" />
        </form>
    </div>
</x-app-layout>
