@props([
    'cancelHref',
    'cancelLabel' => 'Cancelar',
    'submitLabel' => 'Salvar',
])

<div {{ $attributes->merge(['class' => 'flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2']) }}>
    <a href="{{ $cancelHref }}"
       class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">
        {{ $cancelLabel }}
    </a>
    <button type="submit"
        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
        {{ $submitLabel }}
    </button>
</div>
