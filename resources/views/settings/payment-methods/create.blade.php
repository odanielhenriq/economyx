<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">Nova forma de pagamento</h1>
            <a href="{{ route('payment-methods.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
                ← Voltar
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl space-y-6">

        @if ($errors->any())
            <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('payment-methods.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nome</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Ex: Cartão de crédito">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Slug (opcional)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}"
                        class="w-full px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="ex: cartao-de-credito">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
