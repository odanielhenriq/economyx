<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-12 text-center text-sm text-slate-400']) }}>
    {{ $slot ?? 'Carregando…' }}
</div>
