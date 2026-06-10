<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Usuários"
            subtitle="Gerencie contas e roles do sistema."
        />
    </x-slot>

    <div class="space-y-6">
        <x-flash-messages />

        @if ($users->isEmpty())
            <x-empty-state
                title="Nenhum usuário encontrado"
                description="Não há usuários cadastrados no sistema."
            />
        @else
            {{-- Desktop --}}
            <div class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">E-mail</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $user->isDev() ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $user->role->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                                Editar
                                            </a>
                                            @can('impersonate', $user)
                                                <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 text-sm font-medium text-violet-600 hover:text-violet-800"
                                                            title="Entrar como este usuário">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                                        </svg>
                                                        Entrar como
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile --}}
            <div class="md:hidden space-y-3">
                @foreach ($users as $user)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
                        <div>
                            <p class="font-medium text-slate-900">{{ $user->name }}</p>
                            <p class="text-sm text-slate-500">{{ $user->email }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $user->isDev() ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $user->role->label() }}
                        </span>
                        <div class="flex gap-3 pt-1">
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                                Editar
                            </a>
                            @can('impersonate', $user)
                                <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 text-sm font-medium text-violet-600 hover:text-violet-800">
                                        Entrar como
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
