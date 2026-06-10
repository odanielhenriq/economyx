<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Nova forma de pagamento"
            subtitle="Use para organizar como você pagou: Pix, dinheiro, débito, boleto, cheque e outras opções."
            back-href="{{ route('payment-methods.index') }}"
            back-label="Voltar para formas de pagamento"
        />
    </x-slot>

    <div class="max-w-xl space-y-6">

        @if ($errors->any())
            <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
                <p class="font-medium mb-1">Corrija os campos abaixo:</p>
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
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Ex: Pix, Dinheiro, Boleto" required>
                    <x-field-hint>Aparece ao cadastrar transações e contas fixas.</x-field-hint>
                </div>
            </div>

            <x-form-actions cancel-href="{{ route('payment-methods.index') }}" submit-label="Salvar forma de pagamento" />
        </form>
    </div>
</x-app-layout>
