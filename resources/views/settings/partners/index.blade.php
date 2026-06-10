<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Parceiros"
            subtitle="Convide alguém da sua casa para compartilhar transações, cartões e o dashboard."
        />
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <x-flash-messages />

        @if (session('invite_url'))
            <div class="px-4 py-3 text-sm text-blue-800 bg-blue-50 border border-blue-200 rounded-xl break-all" role="status">
                <p class="font-medium mb-1">Link do convite — copie e envie:</p>
                <code>{{ session('invite_url') }}</code>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-1">Convidar parceiro</h2>
            <p class="text-xs text-slate-400 mb-4">Informe o e-mail da pessoa. Você receberá um link para enviar — ela precisa criar conta ou entrar para aceitar.</p>
            <form method="POST" action="{{ route('partners.invite') }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input type="email" name="email" required placeholder="email@exemplo.com"
                    class="flex-1 px-3 py-2 text-sm border border-slate-200 rounded-lg">
                <button type="submit"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg whitespace-nowrap">
                    Gerar link de convite
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Sua rede</h2>
            @if ($partners->isEmpty())
                <x-empty-state
                    title="Nenhum parceiro vinculado"
                    description="Convide alguém da sua casa para compartilhar transações e cartões."
                />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($partners as $partner)
                        <li class="py-2 text-sm text-slate-700">{{ $partner->name }} — {{ $partner->email }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($pending->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Convites pendentes</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($pending as $invite)
                        <li class="text-slate-600">
                            {{ $invite->email }} — expira {{ $invite->expires_at->format('d/m/Y') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
