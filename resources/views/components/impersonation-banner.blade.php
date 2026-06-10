@if (session('impersonator_id'))
    <div class="bg-violet-600 text-white px-4 py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm">
        <p>
            Você está visualizando como <strong>{{ auth()->user()->name }}</strong>
            ({{ auth()->user()->email }}).
        </p>
        <form method="POST" action="{{ route('admin.leave-impersonation') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 font-medium transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Voltar à minha conta
            </button>
        </form>
    </div>
@endif
