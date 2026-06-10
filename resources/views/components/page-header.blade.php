@props([
    'title',
    'subtitle' => null,
    'backHref' => null,
    'backLabel' => 'Voltar',
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between w-full min-w-0']) }}>
    <div class="min-w-0">
        @if ($backHref)
            <a href="{{ $backHref }}"
               class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-800 mb-1 transition">
                ← {{ $backLabel }}
            </a>
        @endif
        <h1 class="text-lg font-semibold text-slate-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-500 leading-snug max-w-2xl hidden sm:block">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
