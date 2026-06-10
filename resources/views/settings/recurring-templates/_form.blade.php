@props([
    'action',
    'method' => 'POST',
    'submitLabel',
    'recurringTemplate' => null,
    'categories',
    'types',
    'paymentMethods',
    'creditCards',
    'users',
])

@php
    $rt = $recurringTemplate;
    $amountVal = (float) old('amount', $rt?->amount ?? 0);
    $totalAmountVal = (float) old('total_amount', $rt?->total_amount ?? 0);
    $fmtAmount = $amountVal > 0 ? 'R$ ' . number_format($amountVal, 2, ',', '.') : '';
    $fmtTotalAmount = $totalAmountVal > 0 ? 'R$ ' . number_format($totalAmountVal, 2, ',', '.') : '';
    $selectedUsers = collect(old('user_ids', $rt?->users?->pluck('id')->all() ?? []));
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-slate-700">O que se repete?</h3>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Descrição</label>
            <input type="text" name="description" value="{{ old('description', $rt?->description) }}"
                class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="Ex: Aluguel, Netflix, Academia" required>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div x-data="{ raw: {{ $amountVal }} }">
                <label class="block text-sm font-medium text-slate-700 mb-1">Valor de cada ocorrência</label>
                <input type="text" inputmode="numeric" required
                    value="{{ $fmtAmount }}"
                    @input="raw = formatCurrency($event)"
                    placeholder="R$ 0,00"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <input type="hidden" name="amount" :value="raw.toFixed(2)">
                <x-field-hint>Quanto você paga a cada repetição (mensal ou anual).</x-field-hint>
            </div>
            <div x-data="{ raw: {{ $totalAmountVal }} }">
                <label class="block text-sm font-medium text-slate-700 mb-1">Valor total <span class="text-slate-400 font-normal">(opcional)</span></label>
                <input type="text" inputmode="numeric"
                    value="{{ $fmtTotalAmount }}"
                    @input="raw = formatCurrency($event)"
                    placeholder="R$ 0,00"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <input type="hidden" name="total_amount" :value="raw > 0 ? raw.toFixed(2) : ''">
                <x-field-hint>Só se houver um total fechado, como um financiamento.</x-field-hint>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-slate-700">Quando se repete?</h3>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Frequência</label>
                <select name="frequency"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="monthly" @selected(old('frequency', $rt?->frequency ?? 'monthly') === 'monthly')>Todo mês</option>
                    <option value="yearly" @selected(old('frequency', $rt?->frequency) === 'yearly')>Uma vez por ano</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Dia do vencimento</label>
                <input type="number" name="day_of_month" min="1" max="31"
                    value="{{ old('day_of_month', $rt?->day_of_month) }}"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    placeholder="Ex: 10">
                <x-field-hint>Dia do mês em que essa conta costuma vencer.</x-field-hint>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Começa em</label>
                <input type="date" name="start_date"
                    value="{{ old('start_date', $rt?->start_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Termina em <span class="text-slate-400 font-normal">(opcional)</span></label>
                <input type="date" name="end_date"
                    value="{{ old('end_date', $rt?->end_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <x-field-hint>Deixe em branco se não souber quando termina.</x-field-hint>
            </div>
        </div>

        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                @checked(old('is_active', $rt?->is_active ?? true))
                class="rounded border-slate-300 text-green-600 focus:ring-green-500">
            <span class="text-sm text-slate-700">Conta ativa (aparece nos próximos meses)</span>
        </label>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <h3 class="text-sm font-semibold text-slate-700">Classificação</h3>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Categoria</label>
                <select name="category_id" required
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Selecione</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $rt?->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                <select name="type_id" required
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Selecione</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected(old('type_id', $rt?->type_id) == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                <x-field-hint>Quase sempre será Despesa.</x-field-hint>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Forma de pagamento</label>
                <select name="payment_method_id"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Selecione</option>
                    @foreach ($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod->id }}" @selected(old('payment_method_id', $rt?->payment_method_id) == $paymentMethod->id)>{{ $paymentMethod->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cartão <span class="text-slate-400 font-normal">(opcional)</span></label>
                <select name="credit_card_id"
                    class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Sem cartão</option>
                    @foreach ($creditCards as $card)
                        <option value="{{ $card->id }}" @selected(old('credit_card_id', $rt?->credit_card_id) == $card->id)>
                            {{ $card->name }}@if ($card->owner?->name) ({{ $card->owner->name }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Quem divide esse gasto?</label>
            <select name="user_ids[]" multiple required
                class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent h-28">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected($selectedUsers->contains($user->id))>{{ $user->name }}</option>
                @endforeach
            </select>
            <x-field-hint>O valor será dividido igualmente entre as pessoas selecionadas.</x-field-hint>
        </div>
    </div>

    <x-form-actions cancel-href="{{ route('recurring-templates.index') }}" :submit-label="$submitLabel" />
</form>
