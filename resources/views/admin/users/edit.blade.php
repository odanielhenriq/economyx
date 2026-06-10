<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Editar usuário"
            subtitle="Altere nome, e-mail ou role do usuário."
            back-href="{{ route('admin.users.index') }}"
            back-label="Voltar para usuários"
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

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nome</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                    <select id="role" name="role" required
                        class="w-full px-3 py-2.5 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        @foreach (\App\Enums\UserRole::cases() as $roleOption)
                            <option value="{{ $roleOption->value }}" @selected(old('role', $user->role->value) === $roleOption->value)>
                                {{ $roleOption->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-form-actions cancel-href="{{ route('admin.users.index') }}" submit-label="Salvar usuário" />
        </form>
    </div>
</x-app-layout>
