<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Editar conta fixa</h2>
            <a href="{{ route('recurring-transactions.index') }}" class="text-sm text-indigo-600 hover:underline">
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('recurring-transactions.update', $recurringTransaction) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <div>
                        <label class="text-sm text-gray-600">Descricao</label>
                        <input type="text" name="description"
                            value="{{ old('description', $recurringTransaction->description) }}"
                            class="mt-1 w-full rounded border-gray-300 text-sm" placeholder="Ex: Aluguel">
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm text-gray-600">Valor</label>
                            <input type="number" step="0.01" name="amount"
                                value="{{ old('amount', $recurringTransaction->amount) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm" placeholder="0,00">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Valor total (opcional)</label>
                            <input type="number" step="0.01" name="total_amount"
                                value="{{ old('total_amount', $recurringTransaction->total_amount) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm" placeholder="0,00">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm text-gray-600">Recorrencia</label>
                            <select name="frequency" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="monthly" @selected(old('frequency', $recurringTransaction->frequency) === 'monthly')>Mensal</option>
                                <option value="yearly" @selected(old('frequency', $recurringTransaction->frequency) === 'yearly')>Anual</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Dia do mes</label>
                            <input type="number" name="day_of_month"
                                value="{{ old('day_of_month', $recurringTransaction->day_of_month) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm" placeholder="Ex: 10">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm text-gray-600">Inicio</label>
                            <input type="date" name="start_date"
                                value="{{ old('start_date', optional($recurringTransaction->start_date)->format('Y-m-d')) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Fim (opcional)</label>
                            <input type="date" name="end_date"
                                value="{{ old('end_date', optional($recurringTransaction->end_date)->format('Y-m-d')) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
                            @checked(old('is_active', $recurringTransaction->is_active))>
                        <label class="text-sm text-gray-600">Ativo</label>
                    </div>
                </div>

                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <div>
                        <label class="text-sm text-gray-600">Categoria</label>
                        <select name="category_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Selecione</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected(old('category_id', $recurringTransaction->category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Tipo</label>
                        <select name="type_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Selecione</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}"
                                    @selected(old('type_id', $recurringTransaction->type_id) == $type->id)>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Forma de pagamento</label>
                        <select name="payment_method_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Selecione</option>
                            @foreach ($paymentMethods as $paymentMethod)
                                <option value="{{ $paymentMethod->id }}"
                                    @selected(old('payment_method_id', $recurringTransaction->payment_method_id) == $paymentMethod->id)>
                                    {{ $paymentMethod->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Cartao (opcional)</label>
                        <select name="credit_card_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                            <option value="">Sem cartao</option>
                            @foreach ($creditCards as $card)
                                <option value="{{ $card->id }}"
                                    @selected(old('credit_card_id', $recurringTransaction->credit_card_id) == $card->id)>
                                    {{ $card->name }}@if ($card->owner?->name) ({{ $card->owner->name }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Usuarios</label>
                        <select name="user_ids[]" multiple class="mt-1 w-full rounded border-gray-300 text-sm">
                            @php
                                $selectedUsers = collect(old('user_ids', $recurringTransaction->users->pluck('id')->all()));
                            @endphp
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected($selectedUsers->contains($user->id))>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Segure Ctrl (Windows) ou Cmd (Mac) para selecionar multiplos.</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
