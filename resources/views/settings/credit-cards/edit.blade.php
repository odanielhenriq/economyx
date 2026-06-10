<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Editar cartão"
            subtitle="Alterações nas datas afetam o cálculo das próximas faturas."
            back-href="{{ route('credit-cards.index') }}"
            back-label="Voltar para meus cartões"
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

        <form method="POST" action="{{ route('credit-cards.update', $creditCard) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Identificação</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nome do cartão</label>
                        <input type="text" name="name" value="{{ old('name', $creditCard->name) }}"
                            class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Apelido <span class="text-slate-400 font-normal">(opcional)</span></label>
                        <input type="text" name="alias" value="{{ old('alias', $creditCard->alias) }}"
                            class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            placeholder="Ex: Cartão roxo">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Datas da fatura</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Dia de fechamento</label>
                        <input type="number" min="1" max="31" name="closing_day"
                            value="{{ old('closing_day', $creditCard->closing_day) }}"
                            class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <x-field-hint>Dia em que a fatura fecha e novas compras passam para o próximo mês.</x-field-hint>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Dia de vencimento</label>
                        <input type="number" min="1" max="31" name="due_day"
                            value="{{ old('due_day', $creditCard->due_day) }}"
                            class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <x-field-hint>Dia em que você costuma pagar a fatura.</x-field-hint>
                    </div>
                    @php
                        $limitVal = (float) old('limit', $creditCard->limit ?? 0);
                        $fmtLimit = $limitVal > 0 ? 'R$ ' . number_format($limitVal, 2, ',', '.') : '';
                    @endphp
                    <div x-data="{ raw: {{ $limitVal }} }">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Limite <span class="text-slate-400 font-normal">(opcional)</span></label>
                        <input type="text" inputmode="numeric"
                            value="{{ $fmtLimit }}"
                            @input="raw = formatCurrency($event)"
                            placeholder="R$ 0,00"
                            class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <input type="hidden" name="limit" :value="raw.toFixed(2)">
                        <x-field-hint>Usado para mostrar quanto do limite já foi utilizado na fatura.</x-field-hint>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-slate-700">Dono e compartilhamento</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">De quem é este cartão?</label>
                        <select name="owner_user_id"
                            class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Outra pessoa (não usa o Economyx)</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('owner_user_id', $creditCard->owner_user_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nome da pessoa</label>
                        <input type="text" name="owner_name"
                            value="{{ old('owner_name', $creditCard->owner_user_id ? null : $creditCard->owner_name) }}"
                            class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Quem pode ver e usar este cartão?</label>
                    <select name="shared_user_ids[]" multiple
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent h-32">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ collect(old('shared_user_ids', $creditCard->users->pluck('id')->all()))->contains($user->id) ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-field-hint>Parceiros selecionados verão faturas e poderão lançar compras neste cartão.</x-field-hint>
                </div>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_shared" value="1" {{ old('is_shared', $creditCard->is_shared) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-slate-700">Cartão compartilhado na rede</span>
                </label>
            </div>

            <x-form-actions cancel-href="{{ route('credit-cards.index') }}" submit-label="Salvar alterações" />
        </form>
    </div>
</x-app-layout>
