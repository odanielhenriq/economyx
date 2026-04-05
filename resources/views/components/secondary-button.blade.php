<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
