<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Nova conta fixa"
            subtitle="Use para despesas que se repetem — aluguel, internet, academia ou assinatura. Elas entram no dashboard todo mês."
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

        <x-info-tip>
            Isso <strong>não lança uma transação agora</strong> — cria um modelo que se repete automaticamente. Para registrar um pagamento único, use Transações → Nova transação.
        </x-info-tip>

        @include('settings.recurring-templates._form', [
            'action' => route('recurring-templates.store'),
            'method' => 'POST',
            'submitLabel' => 'Cadastrar conta fixa',
            'recurringTemplate' => null,
            'categories' => $categories,
            'types' => $types,
            'paymentMethods' => $paymentMethods,
            'creditCards' => $creditCards,
            'users' => $users,
        ])
    </div>
</x-app-layout>
