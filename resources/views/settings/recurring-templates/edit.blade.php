<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Editar conta fixa"
            subtitle="Alterações valem para as próximas ocorrências. Para pausar, desmarque Conta ativa."
            back-href="{{ route('recurring-templates.index') }}"
            back-label="Voltar para contas fixas"
        />
    </x-slot>

    <div class="max-w-3xl space-y-6">

        @if ($errors->any())
            <div class="px-4 py-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl">
                <p class="font-medium mb-1">Corrija os campos abaixo:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @include('settings.recurring-templates._form', [
            'action' => route('recurring-templates.update', $recurringTemplate),
            'method' => 'PUT',
            'submitLabel' => 'Salvar alterações',
            'recurringTemplate' => $recurringTemplate,
            'categories' => $categories,
            'types' => $types,
            'paymentMethods' => $paymentMethods,
            'creditCards' => $creditCards,
            'users' => $users,
        ])
    </div>
</x-app-layout>
