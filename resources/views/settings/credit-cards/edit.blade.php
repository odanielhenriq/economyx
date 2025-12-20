<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Editar cartao</h2>
            <a href="{{ route('credit-cards.index') }}" class="text-sm text-indigo-600 hover:underline">
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
            @include('settings.nav')

            @if ($errors->any())
                <div class="p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('credit-cards.update', $creditCard) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700">Dados do cartao</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Nome</label>
                            <input type="text" name="name" value="{{ old('name', $creditCard->name) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Alias (opcional)</label>
                            <input type="text" name="alias" value="{{ old('alias', $creditCard->alias) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Dia de fechamento</label>
                            <input type="number" min="1" max="31" name="closing_day"
                                value="{{ old('closing_day', $creditCard->closing_day) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Dia de vencimento</label>
                            <input type="number" min="1" max="31" name="due_day"
                                value="{{ old('due_day', $creditCard->due_day) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Limite (R$)</label>
                            <input type="number" step="0.01" min="0" name="limit"
                                value="{{ old('limit', $creditCard->limit) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <h3 class="font-semibold text-gray-700">Dono e compartilhamento</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-gray-600">Dono (usuario da rede)</label>
                            <select name="owner_user_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                <option value="">Outro / externo</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('owner_user_id', $creditCard->owner_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Nome do dono (se externo)</label>
                            <input type="text" name="owner_name"
                                value="{{ old('owner_name', $creditCard->owner_user_id ? null : $creditCard->owner_name) }}"
                                class="mt-1 w-full rounded border-gray-300 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Usuarios com acesso</label>
                        <select name="shared_user_ids[]" multiple
                            class="mt-1 w-full rounded border-gray-300 text-sm h-32">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ collect(old('shared_user_ids', $creditCard->users->pluck('id')->all()))->contains($user->id) ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Selecione quem pode usar/ver este cartao.</p>
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_shared" value="1" {{ old('is_shared', $creditCard->is_shared) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-600">Cartao compartilhado</span>
                    </label>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
